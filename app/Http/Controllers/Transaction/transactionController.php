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

        // Reuse existing pending payment to avoid duplicate Order IDs in Midtrans
        $existingPayment = payment::where('transaction_id', $transaction->id)
            ->where('payment_status', 'pending')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'status' => true,
                'snap_token' => $existingPayment->snap_token,
                'order_id' => $existingPayment->midtrans_order_id,
            ]);
        }

        $orderId = 'TRX-' . $transaction->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->total_harga
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name ?? 'Customer',
                'email' => $transaction->user->email ?? 'customer@example.com'
            ],
            // Explicitly set notification URL to ensure Midtrans knows where to send
            'callbacks' => [
                'finish' => config('app.url') . '/history',
                'notification_url' => config('app.url') . '/api/payment/midtrans/callback'
            ]
        ];

        $snapToken = MidtransService::createSnapToken($params);

        payment::create([
            'transaction_id' => $transaction->id,
            'midtrans_order_id' => $orderId,
            'snap_token' => $snapToken,
            'payment_status' => 'pending'
        ]);

        return response()->json([
            'status' => true,
            'snap_token' => $snapToken,
            'order_id' => $orderId,
        ]);
    }

    public function midtransCallback(Request $request)
    {
        MidtransService::init();

        try {
            // SDK automatically handles signature verification (Security)
            $notif = new \Midtrans\Notification();
        } catch (\Exception $e) {
            \Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        $payload = $notif->getResponse();
        \Log::info('Midtrans Notification:', (array) $payload);

        $orderId = $notif->order_id;
        $transactionStatus = $notif->transaction_status;
        $paymentType = $notif->payment_type;
        $fraudStatus = $notif->fraud_status;

        $payment = payment::where('midtrans_order_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment tidak ditemukan'], 404);
        }

        $isSuccess = false;

        // Robust Success Logic for Production
        if ($transactionStatus == 'capture') {
            if ($paymentType == 'credit_card') {
                if ($fraudStatus != 'challenge') {
                    $isSuccess = true;
                }
            }
        } elseif ($transactionStatus == 'settlement') {
            $isSuccess = true;
        }

        if ($isSuccess) {
            $payment->update(['payment_status' => 'paid']);
            $payment->transaction->update([
                'status' => 'paid',
                'payment_type' => $paymentType
            ]);

            ActivityLog::create([
                'user_id' => $payment->transaction->user_id,
                'action' => 'paid',
                'model' => 'Transaction',
                'model_id' => $payment->transaction_id,
                'description' => "Pembayaran ({$paymentType}) berhasil dikonfirmasi.",
                'properties' => ['order_id' => $orderId, 'amount' => $payment->transaction->total_harga]
            ]);
        } elseif (in_array($transactionStatus, ['cancel', 'expire', 'deny'])) {
            $payment->update(['payment_status' => 'failed']);
            $payment->transaction->update(['status' => 'failed']);
        }

        return response()->json(['status' => true]);
    }

    /**
     * Helper to sync status for Localhost or missed callbacks
     */
    private function syncWithMidtrans($transaction)
    {
        try {
            MidtransService::init();
            $status = \Midtrans\Transaction::status($transaction->payment->midtrans_order_id);
            $res = (array) $status;

            $transactionStatus = $res['transaction_status'] ?? null;
            $paymentType = $res['payment_type'] ?? null;
            $fraudStatus = $res['fraud_status'] ?? 'accept';

            $isSuccess = false;
            if ($transactionStatus == 'capture') {
                if ($paymentType == 'credit_card' && $fraudStatus != 'challenge') {
                    $isSuccess = true;
                }
            } elseif ($transactionStatus == 'settlement') {
                $isSuccess = true;
            }

            if ($isSuccess && $transaction->status !== 'paid') {
                $transaction->payment->update(['payment_status' => 'paid']);
                $transaction->update(['status' => 'paid', 'payment_type' => $paymentType]);
            }
        } catch (\Exception $e) {
            \Log::error("Manual Sync Failed (#{$transaction->id}): " . $e->getMessage());
        }
    }
}
