<?php

namespace Tests\Feature;

use PDO;
use Tests\TestCase;
use App\Models\item;
use App\Models\User;
use App\Models\plate;
use App\Models\transaction;
use App\Http\Resources\TransactionResource;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    public function test_make_transaction(){
        $user = User::factory()->create();

        $response = $this->postJson('/api/transaction/start');

        $response->assertStatus(201)
            ->assertJson([
                'status' => true
            ]);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_scan_transaction_success()
    {
        $user = User::factory()->create();

        $item = Item::factory()->create(['harga' => 10000]);

        $plate = Plate::factory()->create([
            'rfid_uid' => '2023001',
            'item_id' => $item->id
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'total_harga' => 0,
            'status' => 'pending',
            'payment_type' => 'midtrans'
        ]);

        $payload = [
            'plate' => [
                ['rfid_uid' => '2023001']
            ]
        ];

        $response = $this->postJson(
            "/api/transaction/scan/{$transaction->id}",
            $payload
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $transaction->id,
            'plate_id' => $plate->id,
            'harga' => 10000
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'total_harga' => 10000
        ]);
    }

    public function test_scan_transaction_duplicate_plate()
    {
        $item = Item::factory()->create(['harga' => 8000]);

        $plate = Plate::factory()->create([
            'rfid_uid' => '2023002',
            'item_id' => $item->id
        ]);

        $transaction = Transaction::factory()->create([
            'status' => 'pending'
        ]);

        $transaction->details()->create([
            'plate_id' => $plate->id,
            'harga' => 8000
        ]);

        $payload = [
            'plate' => [
                ['rfid_uid' => '2023002']
            ]
        ];

        $response = $this->postJson(
            "/api/transaction/scan/{$transaction->id}",
            $payload
        );

        $response->assertStatus(409)
            ->assertJson([
                'status' => false
            ]);
    }

    public function test_scan_transaction_invalid_transaction()
    {
        $payload = [
            'plate' => [
                ['rfid_uid' => '2023009']
            ]
        ];

        $response = $this->postJson('/api/transaction/scan/999', $payload);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Transaksi tidak valid'
            ]);
    }
}
