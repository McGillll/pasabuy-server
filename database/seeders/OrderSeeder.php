<?php

namespace Database\Seeders;

use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    protected const STATUSES = ['pending', 'confirmed', 'confirmed', 'cancelled'];

    public function run()
    {
        if (PasabuyOrder::exists()) {
            return;
        }

        $customers = User::where('role', 'customer')->whereNotNull('approved_at')->get();

        if ($customers->isEmpty()) {
            return;
        }

        $schedules = PasabuySchedule::whereIn('status', ['open', 'closed'])
            ->with('scheduleProducts.product')
            ->get();

        foreach ($schedules as $schedule) {
            if ($schedule->scheduleProducts->isEmpty()) {
                continue;
            }

            foreach ($customers as $index => $customer) {
                $order = new PasabuyOrder;
                $order->pasabuy_schedule_id = $schedule->id;
                $order->user_id = $customer->id;
                $order->customer_name = $customer->fullName();
                $order->customer_contact = $customer->contact_number;
                $order->status = 'pending';
                $order->total_amount = 0;
                $order->save();

                $total = 0;
                foreach ($schedule->scheduleProducts->random(min(2, $schedule->scheduleProducts->count())) as $scheduleProduct) {
                    $quantity = fake()->numberBetween(1, 3);
                    $subtotal = $scheduleProduct->product->default_price * $quantity;
                    $total += $subtotal;

                    $orderItem = new PasabuyOrderItem;
                    $orderItem->order_id = $order->id;
                    $orderItem->pasabuy_schedule_product_id = $scheduleProduct->id;
                    $orderItem->product_name = $scheduleProduct->product->name;
                    $orderItem->estimated_price = $scheduleProduct->product->default_price;
                    $orderItem->actual_price = $scheduleProduct->actual_price;
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
