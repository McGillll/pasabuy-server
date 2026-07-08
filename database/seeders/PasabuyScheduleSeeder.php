<?php

namespace Database\Seeders;

use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleProduct;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PasabuyScheduleSeeder extends Seeder
{
    protected const SCHEDULES = [
        ['title' => 'Grocery Run - June Week 4', 'description' => 'Fully delivered batch from June', 'status' => 'finished', 'cutoff_days' => -14],
        ['title' => 'Grocery Run - July Week 1', 'description' => 'First batch for July', 'status' => 'closed', 'cutoff_days' => -7],
        ['title' => 'Grocery Run - July Week 2', 'description' => 'Second batch for July', 'status' => 'open', 'cutoff_days' => 3],
    ];

    public function run()
    {
        if (PasabuySchedule::exists()) {
            return;
        }

        $admin = User::where('email', 'admin@pasabuy.test')->first();
        $products = Product::all();

        foreach (self::SCHEDULES as $item) {
            $schedule = new PasabuySchedule;
            $schedule->user_id = $admin->id;
            $schedule->title = $item['title'];
            $schedule->description = $item['description'];
            $schedule->cutoff_at = Carbon::now()->addDays($item['cutoff_days']);
            $schedule->status = $item['status'];
            $schedule->save();

            foreach ($products->random(5) as $product) {
                $scheduleProduct = new PasabuyScheduleProduct;
                $scheduleProduct->pasabuy_schedule_id = $schedule->id;
                $scheduleProduct->product_id = $product->id;
                $scheduleProduct->is_disabled = false;

                if (in_array($item['status'], ['closed', 'finished'], true)) {
                    $scheduleProduct->actual_price = $product->default_price;
                    $scheduleProduct->price_confirmed_at = Carbon::now()->subDays(abs($item['cutoff_days']) - 1);
                }

                $scheduleProduct->save();
            }
        }
    }
}
