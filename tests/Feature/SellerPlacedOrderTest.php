<?php

namespace Tests\Feature;

use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerPlacedOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_can_place_an_order_on_behalf_of_a_walk_in_customer()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $response = $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Auntie Rosa',
            'customer_contact' => '09171234567',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_name', 'Auntie Rosa')
            ->assertJsonPath('data.customer_contact', '09171234567')
            ->assertJsonPath('data.channel', 'seller')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 3);

        $this->assertDatabaseHas('pasabuy_orders', [
            'pasabuy_schedule_id' => $schedule->id,
            'user_id' => null,
            'customer_name' => 'Auntie Rosa',
            'channel' => 'seller',
        ]);
    }

    public function test_seller_placed_order_customer_contact_is_optional()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Neighbor - Unit 5B',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_contact', null);
    }

    public function test_customer_name_is_required()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors('customer_name');
    }

    public function test_seller_cannot_place_an_order_on_another_sellers_schedule()
    {
        $seller = User::factory()->create();
        $otherSeller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $otherSeller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Auntie Rosa',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertForbidden();
    }

    public function test_seller_can_place_an_order_on_a_closed_schedule()
    {
        // Unlike customer ordering, this is a seller/admin action, so it isn't gated by
        // is_open — the seller can still record a walk-in order after closing the schedule.
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'closed']);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Auntie Rosa',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.customer_name', 'Auntie Rosa');
    }

    public function test_seller_cannot_place_an_order_on_a_finished_schedule()
    {
        $seller = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id, 'status' => 'finished']);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Auntie Rosa',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'This pasabuy is finished and no longer accepts new orders.');
    }

    public function test_unverified_seller_cannot_place_an_order()
    {
        $seller = User::factory()->create(['email_verified_at' => null]);
        $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);
        $product = $this->createProduct();

        Sanctum::actingAs($seller, ['admin']);

        $this->postJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/orders", [
            'customer_name' => 'Auntie Rosa',
            'items' => [
                ['product_uuid' => $product->uuid, 'quantity' => 1],
            ],
        ])->assertForbidden();
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
