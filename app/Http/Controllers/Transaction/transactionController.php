<?php

namespace App\Http\Controllers\Transaction;

use App\Models\plate;
use App\Models\transaction;
use Illuminate\Http\Request;
use App\Models\transaction_detail;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\payment;
use App\Models\ActivityLog;
use App\Services\MidtransService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class transactionController extends Controller
{
    /**
     * FEATURE: One-Step Checkout from Frontend Cart
     * Accepts: { items: [{plate_id: 1, quantity: 2, price: 15000}, ...] }
     */
    public function create(Request $request)
    {
        // Log request for debugging
        \Log::info('Transaction Create Request', $request->all());

        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.plate_id' => 'required|exists:plates,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $totalHarga = 0;
            $items = $request->items;

            // 1. Calculate Total
            foreach ($items as $item) {
                $totalHarga += ($item['price'] * $item['quantity']);
            }

            // 2. Create Transaction Header
            $transaction = transaction::create([
                'user_id' => auth()->id() ?? 1, // Use auth user or default
                'total_harga' => $totalHarga,
                'status' => 'pending',
                'payment_type' => 'midtrans',
            ]);

            // 3. Create Transaction Details
            foreach ($items as $item) {
                // Check if 'qty' column exists, otherwise adjust logic or assume '1' if backend schema is different
                // Based on standard POS logic, details should have qty. 
                // However, based on previous file, 'makeTransaction' created empty, then 'scanTransaction' added details.
                // Assuming 'transaction_details' table has 'qty' column. If not, we might need to insert multiple rows or just price.

                transaction_detail::create([
                    'transaction_id' => $transaction->id,
                    'plate_id' => $item['plate_id'],
                    'harga' => $item['price'],
                    // 'qty' => $item['quantity'] // Uncomment if column exists. For RFID usually qty is 1 per row if unique scan.
                    // If plate is unique item, qty is always 1.
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil dibuat',
                'data' => new TransactionResource($transaction)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Transaction History
     */
    public function index(Request $request)
    {
        $query = transaction::with(['details.plate.item', 'payment'])->orderBy('created_at', 'desc');

        // Filter by Date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by Status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $transactions = $query->get();

        return response()->json([
            'status' => true,
            'data' => TransactionResource::collection($transactions)
        ]);
    }

    /**
     * Get Transaction Detail
     */
    public function show($id)
    {
        $transaction = transaction::with(['details.plate.item', 'payment', 'user'])->find($id);

        if (!$transaction) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => new TransactionResource($transaction)
        ]);
    }

    // Existing payment methods
    public function payTransaction(string $id)
    {
        $transaction = transaction::findOrFail($id);

        if ($transaction->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Transaksi tidak bisa dibayar'
            ], 422);
        }

        // Check if midtrans key is set
        if (!config('services.midtrans.server_key')) {
            return response()->json([
                'status' => false,
                'message' => 'Integrasi Midtrans belum dikonfigurasi (Server Key missing)'
            ], 500);
        }

        $orderId = 'TRX-' . $transaction->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->total_harga
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name ?? 'Customer',
                'email' => $transaction->user->email ?? 'user@gmail.com'
            ]
        ];

        $snapToken = MidtransService::createSnapToken($params);

        $payment = payment::updateOrCreate(
            ['transaction_id' => $transaction->id],
            [
                'midtrans_order_id' => $orderId,
                'snap_token' => $snapToken,
                'payment_status' => 'pending'
            ]
        );

        return response()->json([
            'status' => true,
            'snap_token' => $snapToken,
            'order_id' => $orderId,
        ]);
    }

    public function midtransCallback(Request $request)
    {
        $payload = $request->all();

        $payment = payment::where('midtrans_order_id', $payload['order_id'])->first();
        if (!$payment) {
            return response()->json([
                'message' => 'Payment tidak ditemukan',
            ], 404);
        }

        $transactionStatus = $payload['transaction_status'];
        $paymentType = $payload['payment_type'] ?? 'unknown';

        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            $payment->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentType
            ]);
            $payment->transaction->update(['status' => 'paid']);

            // Log successful transaction
            ActivityLog::create([
                'user_id' => $payment->transaction->user_id,
                'action' => 'paid',
                'model' => 'Transaction',
                'model_id' => $payment->transaction_id,
                'description' => "Pembayaran ({$paymentType}) berhasil untuk transaksi #{$payment->transaction_id} sebesar Rp " . number_format($payment->transaction->total_harga, 0, ',', '.'),
                'properties' => ['amount' => $payment->transaction->total_harga, 'order_id' => $payload['order_id'], 'type' => $paymentType]
            ]);

        } elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny'])) {
            $payment->update([
                'payment_status' => 'failed',
                'payment_method' => $paymentType
            ]);
            $payment->transaction->update(['status' => 'failed']);

            // Log failed transaction
            ActivityLog::create([
                'user_id' => $payment->transaction->user_id,
                'action' => 'failed',
                'model' => 'Transaction',
                'model_id' => $payment->transaction_id,
                'description' => "Pembayaran ({$paymentType}) gagal/kadaluwarsa untuk transaksi #{$payment->transaction_id}",
                'properties' => ['status' => $transactionStatus, 'order_id' => $payload['order_id'], 'type' => $paymentType]
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }


}
