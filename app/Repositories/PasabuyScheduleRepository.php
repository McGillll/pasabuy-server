<?php

namespace App\Repositories;

use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Models\OrderItem;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleProduct;

class PasabuyScheduleRepository implements PasabuyScheduleRepositoryInterface
{
    public function all(object $payload)
    {
        return PasabuySchedule::filter((array) $payload)
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return PasabuySchedule::with([
            'scheduleProducts' => fn ($query) => $query->where('is_disabled', false),
            'scheduleProducts.product',
        ])->where('uuid', $uuid)->first();
    }

    public function create(object $payload)
    {
        $schedule = new PasabuySchedule;
        $schedule->user_id = $payload->user_id;
        $schedule->title = $payload->title;
        $schedule->description = $payload->description ?? null;
        $schedule->cutoff_at = $payload->cutoff_at ?? null;
        $schedule->status = $payload->status ?? 'draft';
        $schedule->save();

        return $schedule;
    }

    public function update(string $uuid, object $payload)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        $schedule->title = $payload->title;
        $schedule->description = $payload->description ?? null;
        $schedule->cutoff_at = $payload->cutoff_at ?? null;
        $schedule->status = $payload->status ?? $schedule->status;
        $schedule->save();

        return $schedule;
    }

    public function delete(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();
        $schedule->delete();

        return $schedule;
    }

    public function syncProducts(string $uuid, object $payload)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();
        $requestedProductIds = collect($payload->products)->pluck('product_id')->all();

        $schedule->scheduleProducts()
            ->whereNotIn('product_id', $requestedProductIds)
            ->where('is_disabled', false)
            ->get()
            ->each(function ($scheduleProduct) use ($schedule) {
                $isReferenced = OrderItem::where('product_id', $scheduleProduct->product_id)
                    ->whereHas('order', fn ($query) => $query->where('pasabuy_schedule_id', $schedule->id))
                    ->exists();

                if ($isReferenced) {
                    $scheduleProduct->is_disabled = true;
                    $scheduleProduct->save();
                } else {
                    $scheduleProduct->delete();
                }
            });

        foreach ($payload->products as $product) {
            $scheduleProduct = $schedule->scheduleProducts()
                ->where('product_id', $product['product_id'])
                ->first() ?? new PasabuyScheduleProduct;

            $scheduleProduct->pasabuy_schedule_id = $schedule->id;
            $scheduleProduct->product_id = $product['product_id'];
            $scheduleProduct->price = $product['price'];
            $scheduleProduct->max_quantity = $product['max_quantity'];
            $scheduleProduct->is_disabled = false;
            $scheduleProduct->save();
        }

        return $schedule->fresh([
            'scheduleProducts' => fn ($query) => $query->where('is_disabled', false),
            'scheduleProducts.product',
        ]);
    }

    public function orders(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return $schedule->orders()->with('items')->latest()->get();
    }
}
