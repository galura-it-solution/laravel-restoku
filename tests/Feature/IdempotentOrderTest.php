<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdempotentOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_idempotent_key_returns_same_order(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $menu = Menu::factory()->create();
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

        $response1 = $this->withHeader('Idempotency-Key', 'idem-key-1')
            ->postJson('/api/v1/orders', $payload);

        $response1->assertStatus(200);
        $orderId = $response1->json('data.id');

        $response2 = $this->withHeader('Idempotency-Key', 'idem-key-1')
            ->postJson('/api/v1/orders', $payload);

        $response2->assertStatus(200)
            ->assertJsonPath('data.id', $orderId);
    }

    public function test_idempotent_key_rejects_different_payload(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $menu = Menu::factory()->create();
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

        $this->withHeader('Idempotency-Key', 'idem-key-2')
            ->postJson('/api/v1/orders', $payload)
            ->assertStatus(200);

        $differentPayload = [
            'restaurant_table_id' => $table->id,
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'quantity' => 3,
                ],
            ],
        ];

        $this->withHeader('Idempotency-Key', 'idem-key-2')
            ->postJson('/api/v1/orders', $differentPayload)
            ->assertStatus(409);
    }
}
