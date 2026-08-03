<?php

namespace Database\Seeders;

use App\Models\PasabuyOrderItem;
use Illuminate\Database\Seeder;

class OrderItemProductNameSyncSeeder extends Seeder
{
    public function run()
    {
        PasabuyOrderItem::with('pasabuyScheduleProduct.product')
            ->whereHas('pasabuyScheduleProduct.product')
            ->chunkById(100, function ($orderItems) {
                foreach ($orderItems as $orderItem) {
                    $currentName = $orderItem->pasabuyScheduleProduct->product->name;

                    if ($orderItem->product_name === $currentName) {
                        continue;
                    }

                    $orderItem->product_name = $currentName;
                    $orderItem->save();
                }
            });
    }
}
