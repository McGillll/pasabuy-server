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

    public function findActiveForSchedule(int $userId, int $scheduleId)
    {
        return Order::where('user_id', $userId)
            ->where('pasabuy_schedule_id', $scheduleId)
            ->whereIn('status', ['pending', 'confirmed'])
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
        $total = 0;

        foreach ($payload->items as $item) {
            $orderItem = new OrderItem;
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item['product_id'];
            $orderItem->product_name = $item['product_name'];
            $orderItem->estimated_price = $item['estimated_price'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->subtotal = $item['estimated_price'] * $item['quantity'];
            $orderItem->save();

            $total += $orderItem->subtotal;
        }

        $order->total_amount = $total;
        $order->save();

        return $order->fresh(['items.product', 'pasabuySchedule']);
    }

    public function replaceItems(object $order, object $payload)
    {
        $order->items()->delete();

        return $this->addItems($order, $payload);
    }

    public function orderedQuantity(int $scheduleId, int $productId, ?int $excludeOrderId = null)
    {
        return OrderItem::where('product_id', $productId)
            ->when($excludeOrderId, fn ($query) => $query->where('order_id', '!=', $excludeOrderId))
            ->whereHas('order', function ($query) use ($scheduleId) {
                $query->where('pasabuy_schedule_id', $scheduleId)
                    ->whereIn('status', ['pending', 'confirmed']);
            })
            ->sum('quantity');
    }

    public function cancel(object $order)
    {
        $order->status = 'cancelled';
        $order->save();

        return $order->fresh(['items.product', 'pasabuySchedule']);
    }
}
