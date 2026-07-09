<?php

namespace App\Repositories;

use App\Interfaces\Order\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\OrderItem;

class OrderRepository implements OrderRepositoryInterface
{
    public function findByUuid(string $uuid)
    {
        return Order::with('items.product')->where('uuid', $uuid)->first();
    }

    public function findOwnedByUuid(string $uuid, int $userId)
    {
        return Order::with('items.product', 'pasabuySchedule')
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->first();
    }

    public function findByUuidForSchedule(string $uuid, int $scheduleId)
    {
        return Order::with('items.product', 'pasabuySchedule')
            ->where('uuid', $uuid)
            ->where('pasabuy_schedule_id', $scheduleId)
            ->first();
    }

    public function findActiveForSchedule(int $userId, int $scheduleId)
    {
        return Order::where('user_id', $userId)
            ->where('pasabuy_schedule_id', $scheduleId)
            ->whereIn('status', Order::LIVE_STATUSES)
            ->first();
    }

    public function myOrders(int $userId, object $payload)
    {
        return Order::with('items.product', 'pasabuySchedule')
            ->where('user_id', $userId)
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function updateStatus(string $uuid, object $payload)
    {
        $order = Order::where('uuid', $uuid)->first();
        $order->status = $payload->status;
        $order->save();

        return $order->fresh(['items.product', 'pasabuySchedule']);
    }

    public function create(object $payload)
    {
        $order = new Order;
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
            $orderItem = new OrderItem;
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item['product_id'];
            $orderItem->product_name = $item['product_name'];
            $orderItem->estimated_price = $item['estimated_price'];
            $orderItem->actual_price = $item['actual_price'] ?? null;
            $orderItem->is_out_of_stock = $item['is_out_of_stock'] ?? false;
            $orderItem->quantity = $item['quantity'];
            $orderItem->subtotal = ($item['actual_price'] ?? $item['estimated_price']) * $item['quantity'];
            $orderItem->save();
        }

        $this->recalcTotal($order);

        return $order->fresh(['items.product', 'pasabuySchedule']);
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

        return $order->fresh(['items.product', 'pasabuySchedule']);
    }

    public function findLiveItemsForProduct(int $scheduleId, int $productId)
    {
        return OrderItem::where('product_id', $productId)
            ->where('is_bonus', false)
            ->whereHas('order', fn ($query) => $query->where('pasabuy_schedule_id', $scheduleId)
                ->where('status', 'pending'))
            ->with('order')
            ->get();
    }

    public function applyResolvedPrice(object $item, ?float $actualPrice, ?int $quantity = null)
    {
        if ($quantity !== null) {
            $item->quantity = $quantity;
        }

        $item->actual_price = $actualPrice;
        $item->is_out_of_stock = false;
        $item->subtotal = $actualPrice * $item->quantity;
        $item->save();

        $this->recalcTotal($item->order);

        return $item->fresh();
    }

    public function addOrUpdateBonusItem(object $order, object $scheduleProduct)
    {
        $paidItem = $order->items()
            ->where('product_id', $scheduleProduct->product_id)
            ->where('is_bonus', false)
            ->first();

        if (! $paidItem) {
            return null;
        }

        $bonusItem = $order->items()
            ->where('product_id', $scheduleProduct->product_id)
            ->where('is_bonus', true)
            ->first() ?? new OrderItem;

        $bonusItem->order_id = $order->id;
        $bonusItem->product_id = $scheduleProduct->product_id;
        $bonusItem->product_name = $paidItem->product_name;
        $bonusItem->estimated_price = 0;
        $bonusItem->actual_price = 0;
        $bonusItem->is_bonus = true;
        $bonusItem->quantity = $paidItem->quantity;
        $bonusItem->subtotal = 0;
        $bonusItem->save();

        return $bonusItem;
    }

    public function removeBonusItem(object $order, int $productId)
    {
        $order->items()->where('product_id', $productId)->where('is_bonus', true)->delete();
    }

    public function markItemOutOfStock(object $item)
    {
        $item->is_out_of_stock = true;
        $item->actual_price = null;
        $item->subtotal = 0;
        $item->save();

        $this->recalcTotal($item->order);

        return $item->fresh();
    }

    protected function recalcTotal(Order $order)
    {
        $order->total_amount = $order->items()->sum('subtotal');
        $order->save();
    }
}
