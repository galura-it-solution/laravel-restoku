<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestaurantTableFactory extends Factory
{
    protected $model = RestaurantTable::class;

    public function definition(): array
    {
        return [
            'name' => 'Table ' . $this->faker->unique()->numberBetween(1, 200),
            'status' => 'available',
        ];
    }
}
