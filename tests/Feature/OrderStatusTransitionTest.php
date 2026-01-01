<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_status_transition_is_rejected(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        Sanctum::actingAs($staff);

        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => 'done',
        ]);

        $response->assertStatus(409);
    }
}
