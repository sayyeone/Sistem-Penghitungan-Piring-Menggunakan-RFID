<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->payment?->midtrans_order_id ?? 'TRX-' . $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'name' => $this->user->name ?? 'Guest/Deleted',
                'role' => $this->user->role ?? 'unknown',
            ],
            'total_amount' => (float) $this->total_harga,
            'total_harga' => (float) $this->total_harga,
            'status' => $this->status,
            'payment_type' => $this->payment?->payment_method ?? $this->payment_type,
            'created_at' => $this->created_at,
            'payment' => $this->payment,
            'items' => $this->details->map(function ($detail) {
                return [
                    'plate_name' => $detail->plate?->item?->nama_item ?? 'Unknown Item',
                    'price' => (float) $detail->harga,
                    'quantity' => 1, // RFID is usually 1 plate per entry
                    'subtotal' => (float) $detail->harga
                ];
            }),
            'details' => $this->details ?? []
        ];
    }
}
