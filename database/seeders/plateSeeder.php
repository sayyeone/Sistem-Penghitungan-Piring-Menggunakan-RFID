<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\item;

class plateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = item::all();

        foreach ($items as $item) {
            for ($i = 1; $i <= 5; $i++) {
                $item->plates()->create([
                    'rfid_uid'   => 'R' . $item->id . '00' . $i,
                    'nama_piring'=> $item->nama_item,
                    'harga'      => $item->harga,
                    'status'     => '1',
                ]);
            }
        }
    }
}
