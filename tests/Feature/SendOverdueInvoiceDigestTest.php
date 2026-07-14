<?php

use App\Interfaces\InvoiceSetting\InvoiceSettingServiceInterface;
use App\Mail\OverdueInvoiceDigest;
use App\Models\PasabuyOrder;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function makeOverdueDigestOrder(User $seller, array $overrides = [], $deliveredAt = null)
{
    $schedule = PasabuySchedule::factory()->create(['user_id' => $seller->id]);

    $order = PasabuyOrder::factory()->create(array_merge([
        'pasabuy_schedule_id' => $schedule->id,
        'total_amount' => 500,
        'status' => 'confirmed',
        'payment_status' => 'unpaid',
    ], $overrides));

    // delivered_at is not mass-assignable; set it directly, mirroring OrderRepository::updateDeliveryStatus.
    $order->delivered_at = $deliveredAt;
    $order->save();

    return $order->fresh('pasabuySchedule');
}

test('emails a seller with a genuinely overdue unpaid order', function () {
    Mail::fake();

    $seller = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 10,
        'markup_fixed_amount' => 100,
        'due_days' => 15,
        'due_date_reference' => 'delivery',
        'interest_rate' => 1,
        'interest_period' => 'day',
        'interest_base' => 'full_total',
        'is_active' => true,
    ], $seller->id);

    // delivered 18 days ago -> due 15 days after delivery -> 3 days overdue
    makeOverdueDigestOrder($seller, [], now()->subDays(18));

    $this->artisan('invoices:send-overdue-digest')->assertExitCode(0);

    Mail::assertSent(OverdueInvoiceDigest::class, function ($mail) use ($seller) {
        return $mail->seller->id === $seller->id
            && $mail->rows->count() === 1
            && $mail->hasTo($seller->email);
    });
});

test('does not email a seller whose order is not yet overdue', function () {
    Mail::fake();

    $seller = User::factory()->create();

    // delivered today, default due_days is generous -> not overdue
    makeOverdueDigestOrder($seller, [], now());

    $this->artisan('invoices:send-overdue-digest')->assertExitCode(0);

    Mail::assertNotSent(OverdueInvoiceDigest::class);
});

test('does not email a seller whose overdue order is already fully paid', function () {
    Mail::fake();

    $seller = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 10,
        'markup_fixed_amount' => 100,
        'due_days' => 15,
        'due_date_reference' => 'delivery',
        'interest_rate' => 1,
        'interest_period' => 'day',
        'interest_base' => 'full_total',
        'is_active' => true,
    ], $seller->id);

    $order = makeOverdueDigestOrder($seller, [
        'payment_status' => 'paid',
    ], now()->subDays(18));

    // recorded_by_user_id is not mass-assignable; set it directly, mirroring OrderPaymentRepository::create.
    $payment = new \App\Models\PasabuyOrderPayment;
    $payment->order_id = $order->id;
    $payment->recorded_by_user_id = $seller->id;
    $payment->amount = 600;
    $payment->method = 'cash';
    $payment->paid_at = now();
    $payment->save();

    $this->artisan('invoices:send-overdue-digest')->assertExitCode(0);

    Mail::assertNotSent(OverdueInvoiceDigest::class);
});
