<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\item;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class itemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path("seeders/dataSeed/nasi_padang.csv");
        $file = fopen($path, 'r');

        $header = fgetcsv($file);
        $now = Carbon::now(); // timestamp saat ini

        $data = [];

        while (($row = fgetcsv($file)) !== false) {
            $data[] = [
                'nama_item' => $row[0],
                'kategori'  => $row[1],
                'harga'     => $row[2],
                'status'    => $row[3],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($file);
        Item::insert($data);
    }
}
