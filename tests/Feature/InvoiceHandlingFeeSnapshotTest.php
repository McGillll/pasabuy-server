<?php

namespace Tests\Feature;

use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleInvoiceSetting;
use App\Models\PasabuyScheduleProduct;
use App\Models\PasabuySellerInvoiceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceHandlingFeeSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_handling_fee_update_only_affects_unfinished_schedule_order_invoices()
    {
        $seller = User::factory()->create();
        $customer = User::factory()->create();

        $sellerInvoiceSetting = $this->createSellerInvoiceSetting($seller, 25);

        $finishedSchedule = PasabuySchedule::factory()->create([
            'user_id' => $seller->id,
            'status' => 'finished',
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $this->createScheduleInvoiceSetting($finishedSchedule, $sellerInvoiceSetting, 25);
        $finishedOrder = $this->createOrderWithItem($finishedSchedule, $customer, 500);

        $closedSchedule = PasabuySchedule::factory()->create([
            'user_id' => $seller->id,
            'status' => 'closed',
        ]);
        $closedOrder = $this->createOrderWithItem($closedSchedule, $customer, 500);

        Sanctum::actingAs($seller, ['admin']);

        $this->patchJson('/api/admin/invoice-settings', [
            'markup_threshold_amount' => 1000,
            'markup_percentage' => 10,
            'markup_fixed_amount' => 0,
            'handling_fee' => 75,
            'interest_rate' => 0,
            'interest_period' => 'month',
            'interest_base' => 'outstanding_balance',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.handling_fee', '75.00');

        $this->getJson("/api/admin/orders/{$finishedOrder->uuid}/invoice")
            ->assertOk()
            ->assertJsonPath('data.handling_fee', 25)
            ->assertJsonPath('data.total', 525);

        $this->getJson("/api/admin/orders/{$closedOrder->uuid}/invoice")
            ->assertOk()
            ->assertJsonPath('data.handling_fee', 75)
            ->assertJsonPath('data.total', 575);
    }

    private function createSellerInvoiceSetting(User $seller, float $handlingFee)
    {
        $setting = new PasabuySellerInvoiceSetting;
        $setting->user_id = $seller->id;
        $setting->markup_threshold_amount = 1000;
        $setting->markup_percentage = 10;
        $setting->markup_fixed_amount = 0;
        $setting->handling_fee = $handlingFee;
        $setting->interest_rate = 0;
        $setting->interest_period = 'month';
        $setting->interest_base = 'outstanding_balance';
        $setting->is_active = false;
        $setting->save();

        return $setting;
    }

    private function createScheduleInvoiceSetting(PasabuySchedule $schedule, PasabuySellerInvoiceSetting $source, float $handlingFee)
    {
        $setting = new PasabuyScheduleInvoiceSetting;
        $setting->pasabuy_schedule_id = $schedule->id;
        $setting->source_invoice_setting_id = $source->id;
        $setting->markup_threshold_amount = 1000;
        $setting->markup_percentage = 10;
        $setting->markup_fixed_amount = 0;
        $setting->handling_fee = $handlingFee;
        $setting->interest_rate = 0;
        $setting->interest_period = 'month';
        $setting->interest_base = 'outstanding_balance';
        $setting->is_active = false;
        $setting->save();

        return $setting;
    }

    private function createOrderWithItem(PasabuySchedule $schedule, User $customer, float $subtotal)
    {
        $product = new PasabuyProduct;
        $product->name = 'Test Item';
        $product->default_price = $subtotal;
        $product->default_product_status = 'none';
        $product->is_active = true;
        $product->save();

        $scheduleProduct = new PasabuyScheduleProduct;
        $scheduleProduct->pasabuy_schedule_id = $schedule->id;
        $scheduleProduct->product_id = $product->id;
        $scheduleProduct->actual_price = $subtotal;
        $scheduleProduct->product_status = 'none';
        $scheduleProduct->price_confirmed_at = now();
        $scheduleProduct->is_disabled = false;
        $scheduleProduct->save();

        $order = PasabuyOrder::factory()->create([
            'pasabuy_schedule_id' => $schedule->id,
            'user_id' => $customer->id,
            'status' => 'confirmed',
            'total_amount' => $subtotal,
            'payment_status' => 'unpaid',
        ]);

        $item = new PasabuyOrderItem;
        $item->order_id = $order->id;
        $item->pasabuy_schedule_product_id = $scheduleProduct->id;
        $item->product_name = $product->name;
        $item->estimated_price = $subtotal;
        $item->actual_price = $subtotal;
        $item->quantity = 1;
        $item->bonus_quantity = 0;
        $item->subtotal = $subtotal;
        $item->save();

        return $order;
    }
}
