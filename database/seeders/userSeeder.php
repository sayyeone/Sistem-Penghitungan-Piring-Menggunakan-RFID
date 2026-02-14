<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => "Admin RFID",
            'email' => 'admin@pos.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => '1',
        ]);

        User::create([
            'name' => "Kasir RFID",
            'email' => 'kasir@pos.com',
            'password' => Hash::make('kasir123'),
            'role' => 'kasir',
            'status' => '1',
        ]);
    }
}
