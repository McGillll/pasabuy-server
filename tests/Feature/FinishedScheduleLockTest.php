<?php

use App\Exceptions\InvalidPayloadException;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seller()
{
    return User::factory()->create();
}

function scheduleWithStatus(User $seller, string $status)
{
    $schedule = PasabuySchedule::factory()->create([
        'user_id' => $seller->id,
        'status' => $status,
    ]);

    if ($status === 'finished') {
        $schedule->finished_at = now();
        $schedule->save();
    }

    return $schedule;
}

function scheduleProductFor(PasabuySchedule $schedule)
{
    $product = new PasabuyProduct;
    $product->name = 'Test Product';
    $product->default_price = 100;
    $product->default_product_status = 'none';
    $product->is_active = true;
    $product->save();

    $scheduleProduct = new PasabuyScheduleProduct;
    $scheduleProduct->pasabuy_schedule_id = $schedule->id;
    $scheduleProduct->product_id = $product->id;
    $scheduleProduct->product_status = 'none';
    $scheduleProduct->actual_price = null;
    $scheduleProduct->is_disabled = false;
    $scheduleProduct->save();

    return $scheduleProduct;
}

function orderFor(PasabuySchedule $schedule, PasabuyScheduleProduct $scheduleProduct, User $customer)
{
    $order = PasabuyOrder::factory()->create([
        'pasabuy_schedule_id' => $schedule->id,
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    $item = new PasabuyOrderItem;
    $item->order_id = $order->id;
    $item->pasabuy_schedule_product_id = $scheduleProduct->id;
    $item->product_name = 'Test Product';
    $item->estimated_price = 100;
    $item->actual_price = 100;
    $item->quantity = 2;
    $item->bonus_quantity = 0;
    $item->subtotal = 200;
    $item->save();

    return $order->fresh('items');
}

test('finish() is still a valid terminal transition from closed', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'closed');

    $finished = app(PasabuyScheduleServiceInterface::class)->finish($schedule->uuid, $seller->id);

    expect($finished->status)->toBe('finished')
        ->and($finished->finished_at)->not->toBeNull();
});

test('finish() cannot be called again once already finished', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'finished');

    app(PasabuyScheduleServiceInterface::class)->finish($schedule->uuid, $seller->id);
})->throws(InvalidPayloadException::class);

test('updating a finished schedule fails', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'finished');

    app(PasabuyScheduleServiceInterface::class)->update($schedule->uuid, (object) [
        'user_id' => $seller->id,
        'title' => 'New title',
        'description' => 'New description',
    ]);
})->throws(InvalidPayloadException::class);

test('deleting a finished schedule fails', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'finished');

    app(PasabuyScheduleServiceInterface::class)->delete($schedule->uuid, $seller->id);
})->throws(InvalidPayloadException::class);

test('updating a non-finished schedule still works', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'open');

    $updated = app(PasabuyScheduleServiceInterface::class)->update($schedule->uuid, (object) [
        'user_id' => $seller->id,
        'title' => 'Updated title',
        'description' => 'Updated description',
    ]);

    expect($updated->title)->toBe('Updated title');
});

test('confirming a product price on a finished schedule fails', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'finished');
    $scheduleProduct = scheduleProductFor($schedule);

    app(PasabuyScheduleServiceInterface::class)->confirmProductPrice($schedule->uuid, $scheduleProduct->uuid, (object) [
        'user_id' => $seller->id,
        'product_status' => 'none',
        'actual_price' => 120,
    ]);
})->throws(InvalidPayloadException::class);

test('confirming a product price on a closed schedule still works', function () {
    $seller = seller();
    $schedule = scheduleWithStatus($seller, 'closed');
    $scheduleProduct = scheduleProductFor($schedule);

    $result = app(PasabuyScheduleServiceInterface::class)->confirmProductPrice($schedule->uuid, $scheduleProduct->uuid, (object) [
        'user_id' => $seller->id,
        'product_status' => 'none',
        'actual_price' => 120,
    ]);

    expect((float) $result->schedule_product->actual_price)->toBe(120.0);
});

test('customer editing order items on a finished schedule fails', function () {
    $seller = seller();
    $customer = User::factory()->create();
    $schedule = scheduleWithStatus($seller, 'finished');
    $scheduleProduct = scheduleProductFor($schedule);
    $order = orderFor($schedule, $scheduleProduct, $customer);

    app(OrderServiceInterface::class)->updateItemsForCustomer($order->uuid, (object) [
        'user' => $customer,
        'items' => [
            ['product_uuid' => $scheduleProduct->product->uuid, 'quantity' => 5],
        ],
    ]);
})->throws(InvalidPayloadException::class);

test('customer cancelling an order on a finished schedule fails', function () {
    $seller = seller();
    $customer = User::factory()->create();
    $schedule = scheduleWithStatus($seller, 'finished');
    $scheduleProduct = scheduleProductFor($schedule);
    $order = orderFor($schedule, $scheduleProduct, $customer);

    app(OrderServiceInterface::class)->cancelForCustomer($order->uuid, (object) ['user' => $customer]);
})->throws(InvalidPayloadException::class);

test('seller editing order items on a finished schedule fails', function () {
    $seller = seller();
    $customer = User::factory()->create();
    $schedule = scheduleWithStatus($seller, 'finished');
    $scheduleProduct = scheduleProductFor($schedule);
    $order = orderFor($schedule, $scheduleProduct, $customer);

    app(OrderServiceInterface::class)->updateItemsForSeller($schedule->uuid, $order->uuid, (object) [
        'user_id' => $seller->id,
        'items' => [
            ['product_uuid' => $scheduleProduct->product->uuid, 'quantity' => 5],
        ],
    ]);
})->throws(InvalidPayloadException::class);

test('seller editing order items on a closed schedule still works', function () {
    $seller = seller();
    $customer = User::factory()->create();
    $schedule = scheduleWithStatus($seller, 'closed');
    $scheduleProduct = scheduleProductFor($schedule);
    $order = orderFor($schedule, $scheduleProduct, $customer);

    $updated = app(OrderServiceInterface::class)->updateItemsForSeller($schedule->uuid, $order->uuid, (object) [
        'user_id' => $seller->id,
        'items' => [
            ['product_uuid' => $scheduleProduct->product->uuid, 'quantity' => 5],
        ],
    ]);

    expect($updated->items->first()->quantity)->toBe(5);
});
