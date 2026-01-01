<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->numberBetween(1000, 50000),
            'description' => $this->faker->sentence(),
            'image_object_key' => null,
            'is_active' => true,
        ];
    }
}
