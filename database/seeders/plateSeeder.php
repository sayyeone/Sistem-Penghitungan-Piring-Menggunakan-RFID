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
        $counter = 2023001;

        foreach ($items as $item){
            for ($i = 1; $i <= 5; $i++){
                $item->plates()->create([
                    'rfid_uid' => $counter,
                    'status' => '1'
                ]);
                $counter++;
            }
        }
    }
}
