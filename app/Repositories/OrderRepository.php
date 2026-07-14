<?php

namespace App\Repositories;

use App\Interfaces\Order\OrderRepositoryInterface;
use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;

class OrderRepository implements OrderRepositoryInterface
{
    public function all(object $payload)
    {
        return PasabuyOrder::filter((array) $payload)
            ->with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return PasabuyOrder::with('items.pasabuyScheduleProduct.product', 'pasabuySchedule', 'payments')->where('uuid', $uuid)->first();
    }

    public function findOwnedByUuid(string $uuid, int $userId)
    {
        return PasabuyOrder::with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->first();
    }

    public function findByUuidForSchedule(string $uuid, int $scheduleId)
    {
        return PasabuyOrder::with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')
            ->where('uuid', $uuid)
            ->where('pasabuy_schedule_id', $scheduleId)
            ->first();
    }

    public function findActiveForSchedule(int $userId, int $scheduleId)
    {
        return PasabuyOrder::where('user_id', $userId)
            ->where('pasabuy_schedule_id', $scheduleId)
            ->whereIn('status', PasabuyOrder::LIVE_STATUSES)
            ->first();
    }

    public function myOrders(int $userId, object $payload)
    {
        return PasabuyOrder::with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function updateStatus(string $uuid, object $payload)
    {
        $order = PasabuyOrder::where('uuid', $uuid)->first();
        $order->status = $payload->status;
        $order->save();

        return $order->fresh(['items.pasabuyScheduleProduct.product', 'pasabuySchedule']);
    }

    public function updateDeliveryStatus(string $uuid, object $payload)
    {
        $order = PasabuyOrder::where('uuid', $uuid)->first();
        $order->delivery_status = $payload->delivery_status;
        $order->delivered_at = $payload->delivery_status === 'delivered' ? ($order->delivered_at ?? now()) : null;
        $order->save();

        return $order->fresh(['items.pasabuyScheduleProduct.product', 'pasabuySchedule']);
    }

    public function recalcPaymentStatus(object $order)
    {
        $locked = PasabuyOrder::whereKey($order->id)->lockForUpdate()->first();

        $paid = $locked->payments()->sum('amount');

        $locked->payment_status = match (true) {
            bccomp((string) $paid, '0', 2) <= 0 => 'unpaid',
            bccomp((string) $paid, (string) $locked->total_amount, 2) >= 0 => 'paid',
            default => 'partially_paid',
        };

        $locked->save();

        $order->payment_status = $locked->payment_status;

        return $order;
    }

    public function create(object $payload)
    {
        $order = new PasabuyOrder;
        $order->pasabuy_schedule_id = $payload->pasabuy_schedule_id;
        $order->user_id = $payload->user_id;
        $order->customer_name = $payload->customer_name;
        $order->customer_contact = $payload->customer_contact;
        $order->status = 'pending';
        $order->total_amount = 0;
        $order->save();

        return $order;
    }

    public function addItems(object $order, object $payload)
    {
        foreach ($payload->items as $item) {
            $orderItem = new PasabuyOrderItem;
            $orderItem->order_id = $order->id;
            $orderItem->pasabuy_schedule_product_id = $item['pasabuy_schedule_product_id'];
            $orderItem->product_name = $item['product_name'];
            $orderItem->estimated_price = $item['estimated_price'];
            $orderItem->actual_price = $item['actual_price'] ?? null;
            $orderItem->quantity = $item['quantity'];
            $orderItem->bonus_quantity = $item['bonus_quantity'] ?? 0;
            $orderItem->subtotal = ($item['actual_price'] ?? $item['estimated_price']) * $item['quantity'];
            $orderItem->save();
        }

        $this->recalcTotal($order);

        return $order->fresh(['items.pasabuyScheduleProduct.product', 'pasabuySchedule']);
    }

    public function replaceItems(object $order, object $payload)
    {
        $order->items()->delete();

        return $this->addItems($order, $payload);
    }

    public function cancel(object $order)
    {
        $order->status = 'cancelled';
        $order->save();

        return $order->fresh(['items.pasabuyScheduleProduct.product', 'pasabuySchedule']);
    }

    public function findLiveItemsForScheduleProduct(int $scheduleProductId)
    {
        return PasabuyOrderItem::where('pasabuy_schedule_product_id', $scheduleProductId)
            ->whereHas('order', fn ($query) => $query->whereIn('status', PasabuyOrder::LIVE_STATUSES))
            ->with('order')
            ->get();
    }

    public function applyResolvedPrice(object $item, ?float $actualPrice, ?int $quantity = null, ?int $bonusQuantity = null)
    {
        if ($quantity !== null) {
            $item->quantity = $quantity;
        }

        if ($bonusQuantity !== null) {
            $item->bonus_quantity = $bonusQuantity;
        }

        $item->actual_price = $actualPrice;
        $item->subtotal = $actualPrice !== null ? $actualPrice * $item->quantity : 0;
        $item->save();

        $this->recalcTotal($item->order);

        return $item->fresh();
    }

    protected function recalcTotal(PasabuyOrder $order)
    {
        $order->total_amount = $order->items()->sum('subtotal');
        $order->save();
    }
}
