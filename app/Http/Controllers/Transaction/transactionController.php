<?php

namespace App\Http\Controllers\Transaction;

use App\Models\plate;
use App\Providers\helper;
use App\Models\transaction;
use Illuminate\Http\Request;
use App\Models\transaction_detail;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;

class transactionController extends Controller
{
    // membuat transaction tapi transaction nya kosongan dulu
    public function makeTransaction(){
        $transaction = transaction::create([
            'user_id' => 1,
            'total_harga' => 0,
            'status' => 'pending',
            'payment_type' => 'midtrans',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Transaksi berhasil dibuat, sekarang tambahkan item!',
            'data' => new TransactionResource($transaction)
        ], 201);
    }

    public function scanTransaction(string $id, Request $request)
    {
        $transaction = transaction::find($id);
        if (!$transaction || $transaction->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Transaksi tidak valid'
            ], 404);
        }

        $item = helper::jsonToArray($request->all());

        if (empty($item)) {
            return response()->json([
                'status' => false,
                'message' => 'Data RFID kosong / tidak valid'
            ], 422);
        }

        $plates = plate::with('item')
            ->whereIn('id', array_values($item))
            ->get()
            ->keyBy('id');

        $existingPlateIds = transaction_detail::where('transaction_id', $transaction->id)
            ->pluck('plate_id')
            ->toArray();

        $conflicted = [];
        $inserted  = [];

        foreach ($item as $rfid => $plateId) {

            if (in_array($plateId, $existingPlateIds)) {
                $conflicted[] = $rfid;
                continue;
            }

            $plate = $plates[$plateId] ?? null;

            if (!$plate || !$plate->item) {
                continue;
            }

            transaction_detail::create([
                'transaction_id' => $transaction->id,
                'plate_id' => $plate->id,
                'harga' => $plate->item->harga
            ]);

            $inserted[] = $rfid;
        }

        $transaction->update([
            'total_harga' => transaction_detail::where('transaction_id', $id)->sum('harga')
        ]);

        if (!empty($conflicted)) {
            return response()->json([
                'status' => false,
                'conflict_rfids' => $conflicted,
                'inserted_rfids' => $inserted
            ], 409);
        }

        return response()->json([
            'status' => true,
            'message' => 'Scan RFID berhasil',
            'data' => $inserted
        ], 201);
    }


}
