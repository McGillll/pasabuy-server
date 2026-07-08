<?php

namespace App\Repositories;

use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Models\Order;
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
            'scheduleProducts.product.category',
        ])->where('uuid', $uuid)->first();
    }

    public function updateStatus(string $uuid, string $status)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();
        $schedule->status = $status;
        $schedule->save();

        return $schedule->fresh([
            'scheduleProducts' => fn ($query) => $query->where('is_disabled', false),
            'scheduleProducts.product.category',
        ]);
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
        $requestedProductIds = $payload->products;

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

        foreach ($requestedProductIds as $productId) {
            $scheduleProduct = $schedule->scheduleProducts()
                ->where('product_id', $productId)
                ->first() ?? new PasabuyScheduleProduct;

            $scheduleProduct->pasabuy_schedule_id = $schedule->id;
            $scheduleProduct->product_id = $productId;
            $scheduleProduct->is_disabled = false;
            $scheduleProduct->save();
        }

        return $schedule->fresh([
            'scheduleProducts' => fn ($query) => $query->where('is_disabled', false),
            'scheduleProducts.product.category',
        ]);
    }

    public function orders(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return $schedule->orders()->with('items.product')->latest()->get();
    }

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload)
    {
        $scheduleProduct = PasabuyScheduleProduct::where('uuid', $scheduleProductUuid)
            ->whereHas('pasabuySchedule', fn ($query) => $query->where('uuid', $scheduleUuid))
            ->first();

        if (! $scheduleProduct) {
            return null;
        }

        $scheduleProduct->actual_price = $payload->promo_status === 'out_of_stock' ? null : $payload->actual_price;
        $scheduleProduct->promo_status = $payload->promo_status;
        $scheduleProduct->price_confirmed_at = now();
        $scheduleProduct->save();

        return $scheduleProduct->fresh('product');
    }

    public function findScheduleProduct(int $scheduleId, int $productId)
    {
        return PasabuyScheduleProduct::where('pasabuy_schedule_id', $scheduleId)
            ->where('product_id', $productId)
            ->first();
    }

    public function summary(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return OrderItem::selectRaw('product_id, SUM(quantity) as total_quantity')
            ->where('is_bonus', false)
            ->where('confirmation_status', '!=', 'removed')
            ->whereHas('order', fn ($query) => $query->where('pasabuy_schedule_id', $schedule->id)
                ->whereIn('status', Order::LIVE_STATUSES))
            ->groupBy('product_id')
            ->with('product.category')
            ->get();
    }
}
