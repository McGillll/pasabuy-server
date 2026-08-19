<?php

namespace Tests\Feature;

use App\Models\PasabuyOrder;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_update_order_status()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'closed']);
        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($seller, ['admin']);

        $this->patchJson("/api/admin/orders/{$order->uuid}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_seller_can_update_order_delivery_status()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($seller, ['admin']);

        $this->patchJson("/api/admin/orders/{$order->uuid}/delivery-status", ['delivery_status' => 'delivered'])
            ->assertOk()
            ->assertJsonPath('data.delivery_status', 'delivered');
    }
}
