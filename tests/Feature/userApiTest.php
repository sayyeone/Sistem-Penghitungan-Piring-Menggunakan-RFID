<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class userApiTest extends TestCase
{
    /**
     * A basic feature test example.
     */
//     public function test_example(): void
//     {
//         $response = $this->get('/');

//         $response->assertStatus(200);
//     }

    use RefreshDatabase;

    public function test_can_gett_all_active_users(){
        User::factory()->count(3)->create(['status' => '1']);
        User::factory()->create(['status' => '0']);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)->assertJsonStructure([
            'status',
            'message',
            'data'
        ]);
    }

    public function test_can_create_user(){
        $payload = [
            'name' => 'Adisty',
            'email' => 'adistyfatikaardani@gmail.com',
            'password' => '12345678',
            'role' => 'employee'
        ];

        $response = $this->postJson('/api/user', $payload);

        $response->assertStatus(201)->assertJson(['status' => true]);

        $this->assertDatabaseHas('users',[
            'email' => 'adistyfatikaardani@gmail.com',
            'status' => '1'
        ]);
    }

    public function test_can_show_user_detail(){
        $user = User::factory()->create(['status' => '1']);

        $response = $this->getJson('/api/user/'. $user->id);

        $response->assertStatus(200)->assertJson(['status' => true]);
    }

    public function test_cannot_show_inactive_user(){
        $user = user::factory()->create(['status' => '0']);

        $response = $this->getJson('/api/user/'. $user->id);

        $response->assertStatus(404);
    }

    public function test_can_update_user(){
        $user = User::factory()->create(['status' => '1']);

        $payload = [
            'name' => 'pacarku adisty',
            'email' => 'ayang@gmail.com',
            'password' => 'passwordbaru'
        ];

        $response = $this->putJson('/api/user/'.$user->id, $payload);

        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('users', [
                        'email' => 'ayang@gmail.com',
        ]);
    }

    public function test_email_unique_update(){
        $user = User::factory()->create(['email' => 'test1@gmail.com']);
        $user2 = user::factory()->create(['email' => 'test2@gmail.com']);

        $response = $this->putJson('/api/user/'. $user2->id, [
            'name' => 'test',
            'email' => 'test1@gmail.com'
        ]);

        $response->assertStatus(422);
    }

    public function test_soft_delete_user(){
        $user = User::factory()->create(['status' => '1']);

        $response = $this->deleteJson('/api/user/'. $user->id);

        $response->assertStatus(200)->assertJson(['status' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => '0',
        ]);
    }
}
