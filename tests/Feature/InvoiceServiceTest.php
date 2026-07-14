<?php

use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingServiceInterface;
use App\Models\PasabuyOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrder(User $seller, float $total, $deliveredAt = null, $scheduleFinishedAt = null)
{
    $schedule = \App\Models\PasabuySchedule::factory()->create(['user_id' => $seller->id]);

    // finished_at is not mass-assignable; set it directly, mirroring PasabuyScheduleRepository::updateStatus.
    $schedule->finished_at = $scheduleFinishedAt;
    $schedule->save();

    $order = PasabuyOrder::factory()->create([
        'pasabuy_schedule_id' => $schedule->id,
        'total_amount' => $total,
    ]);

    // delivered_at is not mass-assignable; set it directly, mirroring OrderRepository::updateDeliveryStatus.
    $order->delivered_at = $deliveredAt;
    $order->save();

    return $order->fresh('pasabuySchedule');
}

test('markup is a flat fee when total is at or below the threshold', function () {
    $seller = User::factory()->create();
    $order = makeOrder($seller, 1000);

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['markup_amount'])->toBe(100.0)
        ->and($invoice['invoice_total'])->toBe(1100.0);
});

test('markup is a percentage of total when total exceeds the threshold', function () {
    $seller = User::factory()->create();
    $order = makeOrder($seller, 1500);

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['markup_amount'])->toBe(150.0)
        ->and($invoice['invoice_total'])->toBe(1650.0);
});

test('due date is null when the order is undelivered and the schedule is unfinished', function () {
    $seller = User::factory()->create();
    $order = makeOrder($seller, 500);

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['due_date'])->toBeNull()
        ->and($invoice['is_overdue'])->toBeFalse()
        ->and($invoice['interest_amount'])->toBe(0.0);
});

test('no interest accrues before the due date', function () {
    $seller = User::factory()->create();
    $order = makeOrder($seller, 500, now());

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['is_overdue'])->toBeFalse()
        ->and($invoice['interest_amount'])->toBe(0.0)
        ->and($invoice['total_payable'])->toBe($invoice['invoice_total']);
});

test('interest compounds per elapsed day once overdue', function () {
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
    $order = makeOrder($seller, 500, now()->subDays(18));

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    $expectedInterest = 600 * ((1.01 ** 3) - 1); // invoice_total = 500 + 100 fixed markup = 600

    expect($invoice['is_overdue'])->toBeTrue()
        ->and($invoice['elapsed_periods'])->toBe(3)
        ->and($invoice['interest_amount'])->toBe(round($expectedInterest, 2));
});

test('outstanding_balance interest base shrinks as payments are recorded', function () {
    $seller = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 10,
        'markup_fixed_amount' => 100,
        'due_days' => 15,
        'due_date_reference' => 'delivery',
        'interest_rate' => 1,
        'interest_period' => 'day',
        'interest_base' => 'outstanding_balance',
        'is_active' => true,
    ], $seller->id);

    $order = makeOrder($seller, 500, now()->subDays(16)); // 1 day overdue, invoice_total = 600

    // recorded_by_user_id is not mass-assignable; set it directly, mirroring OrderPaymentRepository::create.
    $payment = new \App\Models\PasabuyOrderPayment;
    $payment->order_id = $order->id;
    $payment->recorded_by_user_id = $seller->id;
    $payment->amount = 300;
    $payment->method = 'cash';
    $payment->paid_at = now();
    $payment->save();

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    $expectedInterest = 300 * ((1.01 ** 1) - 1); // base = 600 - 300 paid

    expect($invoice['amount_paid'])->toBe(300.0)
        ->and($invoice['interest_amount'])->toBe(round($expectedInterest, 2));
});

test('each seller applies their own invoice rules independently', function () {
    $sellerA = User::factory()->create();
    $sellerB = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 20,
        'markup_fixed_amount' => 50,
        'due_days' => 15,
        'due_date_reference' => 'delivery',
        'interest_rate' => 1,
        'interest_period' => 'month',
        'interest_base' => 'outstanding_balance',
        'is_active' => true,
    ], $sellerA->id);

    $orderA = makeOrder($sellerA, 500); // below threshold -> fixed 50
    $orderB = makeOrder($sellerB, 500); // default settings -> fixed 100

    $invoiceA = app(InvoiceServiceInterface::class)->buildForOrder($orderA);
    $invoiceB = app(InvoiceServiceInterface::class)->buildForOrder($orderB);

    expect($invoiceA['markup_amount'])->toBe(50.0)
        ->and($invoiceB['markup_amount'])->toBe(100.0);
});

test('due date is based on delivery when due_date_reference is delivery, ignoring schedule finish', function () {
    $seller = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 10,
        'markup_fixed_amount' => 100,
        'due_days' => 15,
        'due_date_reference' => 'delivery',
        'interest_rate' => 1,
        'interest_period' => 'month',
        'interest_base' => 'outstanding_balance',
        'is_active' => true,
    ], $seller->id);

    // schedule finished 30 days ago, but order itself was never marked delivered -> due date stays null
    $order = makeOrder($seller, 500, null, now()->subDays(30));

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['due_date'])->toBeNull()
        ->and($invoice['is_overdue'])->toBeFalse();
});

test('due date is based on schedule finish when due_date_reference is schedule_finish', function () {
    $seller = User::factory()->create();

    app(InvoiceSettingServiceInterface::class)->update((object) [
        'markup_threshold_amount' => 1000,
        'markup_percentage' => 10,
        'markup_fixed_amount' => 100,
        'due_days' => 15,
        'due_date_reference' => 'schedule_finish',
        'interest_rate' => 1,
        'interest_period' => 'month',
        'interest_base' => 'outstanding_balance',
        'is_active' => true,
    ], $seller->id);

    // schedule finished 20 days ago, due 15 days after -> overdue, even though the order itself is undelivered
    $order = makeOrder($seller, 500, null, now()->subDays(20));

    $invoice = app(InvoiceServiceInterface::class)->buildForOrder($order);

    expect($invoice['due_date'])->not->toBeNull()
        ->and($invoice['is_overdue'])->toBeTrue();
});
