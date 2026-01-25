<?php

namespace Database\Factories;

use App\Models\item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = item::class;

    public function definition()
    {
        return [
            'nama_item' => $this->faker->word(),
            'harga' => $this->faker->numberBetween(5000, 20000),
        ];
    }
}
