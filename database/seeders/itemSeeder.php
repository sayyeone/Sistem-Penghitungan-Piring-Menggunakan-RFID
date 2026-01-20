<?php

namespace Database\Seeders;

use App\Models\item;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class itemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        item::insert([
            [
                // 1
                'nama_item' => 'Bebek Goreng',
                'kategori' => 'makanan',
                'harga' => '15000',
                'status' => '1',
            ],[
                // 2
                'nama_item' => 'Ayam Bakar',
                'kategori' => 'makanan',
                'harga' => '12000',
                'status' => '1',
            ],[
                // 3
                'nama_item' => 'Es Teh',
                'kategori' => 'minuman',
                'harga' => '5000',
                'status' => '1',
            ],[
                // 4
                'nama_item' => 'Es Jeruk',
                'kategori' => 'minuman',
                'harga' => '5000',
                'status' => '1',
            ],[
                // 5
                'nama_item' => 'Pudding Coklat',
                'kategori' => 'dessert',
                'harga' => '10000',
                'status' => '1',
            ],[
                // 6
                'nama_item' => 'Pisang Goreng (6)',
                'kategori' => 'dessert',
                'harga' => '10000',
                'status' => '1',
            ],[
                // 7
                'nama_item' => 'Krupuk Udang (2)',
                'kategori' => 'camilan',
                'harga' => '4000',
                'status' => '1',
            ],[
                // 8
                'nama_item' => 'Krupuk Rambak (8)',
                'kategori' => 'camilan',
                'harga' => '8000',
                'status' => '1',
            ],[
                // 9
                'nama_item' => 'Nasi Ayam Bakar + Esteh',
                'kategori' => 'paket',
                'harga' => '15000',
                'status' => '1',
            ],[
                // 10
                'nama_item' => 'Nasi Gurami bakar + Es Doger',
                'kategori' => 'paket',
                'harga' => '20000',
                'status' => '1',
            ],[
                // 11
                'nama_item' => 'Nasi',
                'kategori' => 'tambahan',
                'harga' => '6000',
                'status' => '1',
            ],[
                // 12
                'nama_item' => 'Sayur Singkong',
                'kategori' => 'tambahan',
                'harga' => '4000',
                'status' => '1',
            ],[
                // 13
                'nama_item' => 'Sambel Ijo',
                'kategori' => 'tambahan',
                'harga' => '3000',
                'status' => '1'
            ]
        ]);
    }
}
