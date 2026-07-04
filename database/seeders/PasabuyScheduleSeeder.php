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
        ['title' => 'Grocery Run - July Week 1', 'description' => 'First batch for July', 'status' => 'closed', 'cutoff_days' => -7],
        ['title' => 'Grocery Run - July Week 2', 'description' => 'Second batch for July', 'status' => 'open', 'cutoff_days' => 3],
        ['title' => 'Grocery Run - July Week 3', 'description' => 'Draft batch, not yet published', 'status' => 'draft', 'cutoff_days' => 10],
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
                $scheduleProduct->price = fake()->randomFloat(2, 20, 500);
                $scheduleProduct->max_quantity = fake()->numberBetween(10, 50);
                $scheduleProduct->is_disabled = false;
                $scheduleProduct->save();
            }
        }
    }
}
