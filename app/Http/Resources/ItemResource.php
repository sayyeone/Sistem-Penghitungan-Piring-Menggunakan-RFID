<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    // konstruktor untuk return value agar tidak mudah di hacking dari timestamp
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_item' => $this->nama_item,
            'kategori' => $this->kategori,
            'harga' => $this->harga,
            'status' => $this->status
        ];

    }
}
