<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    protected const STATUSES = ['pending', 'confirmed', 'confirmed', 'cancelled'];

    public function run()
    {
        if (Order::exists()) {
            return;
        }

        $customers = User::where('role', 'customer')->whereNotNull('approved_at')->get();

        if ($customers->isEmpty()) {
            return;
        }

        $schedules = PasabuySchedule::whereIn('status', ['open', 'closed'])
            ->with('scheduleProducts')
            ->get();

        foreach ($schedules as $schedule) {
            if ($schedule->scheduleProducts->isEmpty()) {
                continue;
            }

            foreach ($customers as $index => $customer) {
                $order = new Order;
                $order->pasabuy_schedule_id = $schedule->id;
                $order->user_id = $customer->id;
                $order->customer_name = $customer->fullName();
                $order->customer_contact = $customer->contact_number;
                $order->status = self::STATUSES[$index] ?? 'pending';
                $order->total_amount = 0;
                $order->save();

                $total = 0;
                foreach ($schedule->scheduleProducts->random(min(2, $schedule->scheduleProducts->count())) as $scheduleProduct) {
                    $quantity = fake()->numberBetween(1, 3);
                    $subtotal = $scheduleProduct->price * $quantity;
                    $total += $subtotal;

                    $orderItem = new OrderItem;
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $scheduleProduct->product_id;
                    $orderItem->product_name = $scheduleProduct->product->name;
                    $orderItem->product_price = $scheduleProduct->price;
                    $orderItem->quantity = $quantity;
                    $orderItem->subtotal = $subtotal;
                    $orderItem->save();
                }

                $order->total_amount = $total;
                $order->save();
            }
        }
    }
}
