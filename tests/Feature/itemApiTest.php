<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\item;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    // index method
    public function test_can_get_all_items()
    {
        item::factory()->create([
            'nama_item' => 'Piring Besar',
            'harga' => 10000
        ]);

        $response = $this->getJson('/api/item');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Data Berhasil Diambil!'
                ]);
    }

    // post method 201
    public function test_can_create_item()
    {
        $data = [
            'nama_item' => 'Piring Kecil',
            'harga' => 5000
        ];

        $response = $this->postJson('/api/item', $data);

        $response->assertStatus(201)
                ->assertJson([
                    'status' => true,
                    'message' => 'Item berhasil ditambahkan!'
                ]);

        $this->assertDatabaseHas('items', $data);
    }

    // post method 422
    public function test_store_validation_error()
    {
        $response = $this->postJson('/api/item', []);

        $response->assertStatus(422)
                ->assertJson([
                    'status' => false
                ]);
    }


    // method show item $id 200
    public function test_can_show_item_detail()
    {
        $item = item::factory()->create();

        $response = $this->getJson("/api/item/{$item->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Item ditemukan'
                ]);
    }

    // method update 200
    public function test_can_update_item()
    {
        $item = item::factory()->create();

        $data = [
            'nama_item' => 'Piring Update',
            'harga' => 12000
        ];

        $response = $this->putJson("/api/item/{$item->id}", $data);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Item Berhasil Diupdate!'
                ]);

        $this->assertDatabaseHas('items', $data);
    }


    // delete method 200
    public function test_can_delete_item()
    {
        $item = item::factory()->create();

        $response = $this->deleteJson("/api/item/{$item->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                    'message' => 'Item Berhasil DIhapus!'
                ]);

        $this->assertDatabaseMissing('items', [
            'id' => $item->id
        ]);
    }

}
