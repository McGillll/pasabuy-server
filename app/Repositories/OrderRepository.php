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

    public function updateStatus(string $uuid, object $payload)
    {
        $order = Order::where('uuid', $uuid)->first();
        $order->status = $payload->status;
        $order->save();

        return $order;
    }

    public function create(object $payload)
    {
        $order = new Order;
        $order->pasabuy_schedule_id = $payload->pasabuy_schedule_id;
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
            $orderItem->product_price = $item['product_price'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->subtotal = $item['product_price'] * $item['quantity'];
            $orderItem->save();

            $total += $orderItem->subtotal;
        }

        $order->total_amount = $total;
        $order->save();

        return $order->fresh('items.product');
    }

    public function orderedQuantity(int $scheduleId, int $productId)
    {
        return OrderItem::where('product_id', $productId)
            ->whereHas('order', function ($query) use ($scheduleId) {
                $query->where('pasabuy_schedule_id', $scheduleId)
                    ->whereIn('status', ['pending', 'confirmed']);
            })
            ->sum('quantity');
    }
}
