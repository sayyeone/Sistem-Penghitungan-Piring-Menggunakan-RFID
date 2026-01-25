<?php

namespace Database\Factories;

use App\Models\item;
use App\Models\plate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plate>
 */
class PlateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'item_id' => item::factory(),
            'rfid_uid' => $this->faker->unique()->uuid,
            'status' => 1
        ];
    }
}
