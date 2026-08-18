<?php

namespace Tests\Feature;

use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PasabuyScheduleSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_uses_default_price_before_confirmation()
    {
        $summary = $this->summaryFor([
            'default_price' => 289,
            'quantity' => 8,
        ]);

        $summary->assertJsonPath('data.0.default_price', 289)
            ->assertJsonPath('data.0.unit_price', 289)
            ->assertJsonPath('data.0.actual_price', null)
            ->assertJsonPath('data.0.product_status', 'none')
            ->assertJsonPath('data.0.total_quantity', 8)
            ->assertJsonPath('data.0.subtotal', 2312);
    }

    public function test_summary_uses_actual_price_after_confirmation()
    {
        $summary = $this->summaryFor([
            'default_price' => 289,
            'actual_price' => 260,
            'price_confirmed_at' => now(),
            'quantity' => 8,
        ]);

        $summary->assertJsonPath('data.0.default_price', 289)
            ->assertJsonPath('data.0.unit_price', 260)
            ->assertJsonPath('data.0.actual_price', 260)
            ->assertJsonPath('data.0.subtotal', 2080);
    }

    public function test_summary_zeroes_out_of_stock_subtotal()
    {
        $summary = $this->summaryFor([
            'default_price' => 289,
            'product_status' => 'out_of_stock',
            'quantity' => 8,
        ]);

        $summary->assertJsonPath('data.0.unit_price', 289)
            ->assertJsonPath('data.0.product_status', 'out_of_stock')
            ->assertJsonPath('data.0.subtotal', 0);
    }

    public function test_summary_does_not_charge_b1t1_bonus_quantity()
    {
        $summary = $this->summaryFor([
            'default_price' => 289,
            'actual_price' => 260,
            'product_status' => 'b1t1',
            'price_confirmed_at' => now(),
            'quantity' => 8,
        ]);

        $summary->assertJsonPath('data.0.unit_price', 260)
            ->assertJsonPath('data.0.product_status', 'b1t1')
            ->assertJsonPath('data.0.total_quantity', 8)
            ->assertJsonPath('data.0.subtotal', 2080);
    }

    private function summaryFor(array $payload)
    {
        $admin = User::factory()->create();
        $customer = User::factory()->create();
        $schedule = PasabuySchedule::factory()->create(['user_id' => $admin->id]);

        $product = new PasabuyProduct;
        $product->name = 'Member\'s Value Banana Chips with Honey';
        $product->default_price = $payload['default_price'];
        $product->default_product_status = 'none';
        $product->is_active = true;
        $product->save();

        $scheduleProduct = new PasabuyScheduleProduct;
        $scheduleProduct->pasabuy_schedule_id = $schedule->id;
        $scheduleProduct->product_id = $product->id;
        $scheduleProduct->actual_price = $payload['actual_price'] ?? null;
        $scheduleProduct->product_status = $payload['product_status'] ?? 'none';
        $scheduleProduct->price_confirmed_at = $payload['price_confirmed_at'] ?? null;
        $scheduleProduct->is_disabled = false;
        $scheduleProduct->save();

        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);

        $orderItem = new PasabuyOrderItem;
        $orderItem->order_id = $order->id;
        $orderItem->pasabuy_schedule_product_id = $scheduleProduct->id;
        $orderItem->product_name = $product->name;
        $orderItem->estimated_price = $product->default_price;
        $orderItem->actual_price = $scheduleProduct->actual_price;
        $orderItem->quantity = $payload['quantity'];
        $orderItem->bonus_quantity = $scheduleProduct->product_status === 'b1t1' ? $payload['quantity'] : 0;
        $orderItem->subtotal = ($scheduleProduct->actual_price ?? $product->default_price) * $payload['quantity'];
        $orderItem->save();

        Sanctum::actingAs($admin, ['admin']);

        return $this->getJson("/api/admin/pasabuy-schedules/{$schedule->uuid}/summary");
    }
}
