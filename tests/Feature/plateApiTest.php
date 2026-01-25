<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\item;
use App\Models\plate;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class plateApiTest extends TestCase
{

    use RefreshDatabase;

    public function test_bisa_mengambil_semua_data_plate()
    {
        $item = item::factory()->create();

        plate::factory()->create([
            'item_id' => $item->id
        ]);

        $response = $this->getJson('/api/plate');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data'
                 ]);
    }

    public function test_bisa_menambahkan_plate()
    {
        $item = item::factory()->create();

        $response = $this->postJson('/api/plate', [
            'item_id' => $item->id,
            'rfid_uid' => 'RFID12345'
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => true
                 ]);

        $this->assertDatabaseHas('plates', [
            'rfid_uid' => 'RFID12345'
        ]);
    }

    public function test_bisa_menampilkan_detail_plate()
    {
        $item = item::factory()->create();

        $plate = plate::factory()->create([
            'item_id' => $item->id
        ]);

        $response = $this->getJson('/api/plate/' . $plate->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true
                 ]);
    }

    public function test_bisa_update_plate()
    {
        $item = item::factory()->create();
        $itemBaru = item::factory()->create();

        $plate = plate::factory()->create([
            'item_id' => $item->id,
            'rfid_uid' => 'RFID001'
        ]);

        $response = $this->putJson('/api/plate/' . $plate->id, [
            'item_id' => $itemBaru->id,
            'rfid_uid' => '1000110',
            'status' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('plates', [
            'rfid_uid' => '1000110'
        ]);
    }

    public function test_bisa_menghapus_plate()
    {
        $item = item::factory()->create();

        $plate = plate::factory()->create([
            'item_id' => $item->id
        ]);

        $response = $this->deleteJson('/api/plate/' . $plate->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => true
                 ]);

        $this->assertDatabaseMissing('plates', [
            'id' => $plate->id
        ]);
    }

    public function test_delete_plate_yang_tidak_ada()
    {
        $response = $this->deleteJson('/api/plate/999');

        $response->assertStatus(404)
                 ->assertJson([
                     'status' => false
                 ]);
    }
}
