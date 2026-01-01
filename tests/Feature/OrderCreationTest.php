<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_requires_idempotency_key(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $menu = Menu::factory()->create(['stock' => 5]);
        $table = RestaurantTable::factory()->create();

        $payload = [
            'restaurant_table_id' => $table->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                ],
            ],
        ];

        $this->postJson('/api/v1/orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_create_order_decrements_stock_and_occupies_table(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $menu = Menu::factory()->create([
            'stock' => 2,
            'is_active' => true,
        ]);
        $table = RestaurantTable::factory()->create();

        $payload = [
            'restaurant_table_id' => $table->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $this->withHeader('Idempotency-Key', 'idem-key-stock')
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(200);

        $menu->refresh();
        $table->refresh();

        $this->assertSame(0, $menu->stock);
        $this->assertFalse((bool) $menu->is_active);
        $this->assertSame(RestaurantTable::STATUS_OCCUPIED, $table->status);
    }

    public function test_create_order_rejects_active_table_for_other_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $menu = Menu::factory()->create(['stock' => 10]);
        $table = RestaurantTable::factory()->create();

        $payload = [
            'restaurant_table_id' => $table->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 1,
                ],
            ],
        ];

        Sanctum::actingAs($userA);
        $this->withHeader('Idempotency-Key', 'idem-key-user-a')
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(200);

        Sanctum::actingAs($userB);
        $this->withHeader('Idempotency-Key', 'idem-key-user-b')
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(409);
    }
}
