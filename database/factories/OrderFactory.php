<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'restaurant_table_id' => RestaurantTable::factory(),
            'status' => 'pending',
            'total_price' => $this->faker->numberBetween(1000, 50000),
        ];
    }
}
