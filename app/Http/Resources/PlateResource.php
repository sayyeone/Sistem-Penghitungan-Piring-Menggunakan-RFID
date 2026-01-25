<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlateResource extends JsonResource
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
            'rfid_uid' => $this->rfid_uid,
            'status' => $this->status,
            'item' => [ // relasi ke tabel items
                'id' => $this->item->id,
                'nama_item' => $this->item->nama_item,
                'kategori' => $this->item->kategori,
                'harga' => $this->item->harga,
                'status' => $this->item->status
            ]
        ];
    }
}
