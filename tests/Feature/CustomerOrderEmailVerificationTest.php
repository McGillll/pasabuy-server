<?php

namespace Tests\Feature;

use App\Models\PasabuyOrder;
use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerOrderEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_approved_customer_can_add_items_to_blue_basket()
    {
        $customer = User::factory()->unverified()->create(['approved_at' => now()]);
        $product = $this->createProduct();

        Sanctum::actingAs($customer, ['customer']);

        $this->postJson('/api/customer/basket/items', [
            'product_uuid' => $product->uuid,
            'quantity' => 1,
        ])->assertCreated()
            ->assertJsonPath('message', 'Added to Blue Basket');
    }

    public function test_unverified_approved_customer_cannot_place_schedule_order()
    {
        $customer = User::factory()->unverified()->create(['approved_at' => now()]);
        $seller = User::factory()->create(['role' => 'admin']);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($customer, ['customer']);

        $this->postJson("/api/customer/pasabuy-schedules/{$schedule->uuid}/orders", [
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertForbidden()
            ->assertJsonPath('message', 'Please verify your email address before continuing.');

        $this->assertDatabaseMissing('pasabuy_orders', [
            'pasabuy_schedule_id' => $schedule->id,
            'user_id' => $customer->id,
        ]);
    }

    public function test_unverified_approved_customer_cannot_update_schedule_order_items()
    {
        $customer = User::factory()->unverified()->create(['approved_at' => now()]);
        $seller = User::factory()->create(['role' => 'admin']);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);
        $product = $this->createProduct();

        Sanctum::actingAs($customer, ['customer']);

        $this->putJson("/api/customer/orders/{$order->uuid}", [
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 2],
            ],
        ])->assertForbidden()
            ->assertJsonPath('message', 'Please verify your email address before continuing.');
    }

    private function createProduct()
    {
        $product = new PasabuyProduct;
        $product->name = 'Cheez Whiz Pimiento';
        $product->description = '440g';
        $product->is_active = true;
        $product->default_price = 197;
        $product->default_product_status = 'none';
        $product->save();

        return $product;
    }
}
