<?php

namespace App\Repositories;

use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuyProduct;
use App\Models\PasabuySchedule;
use App\Models\PasabuyScheduleChecklistItem;
use App\Models\PasabuyScheduleProduct;
use App\Traits\InteractsWithCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasabuyScheduleRepository implements PasabuyScheduleRepositoryInterface
{
    use InteractsWithCache;

    public function all(object $payload)
    {
        return PasabuySchedule::filter((array) $payload)
            ->where('user_id', $payload->seller_id)
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function openForCustomer(object $payload)
    {
        return PasabuySchedule::filter((array) $payload)
            ->where('status', 'open')
            ->where(function ($query) {
                $query->whereNull('pasabuy_schedule_date')
                    ->orWhereDate('pasabuy_schedule_date', '>', now()->toDateString());
            })
            ->with('user')
            ->withCount('scheduleProducts')
            ->latest()
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        $version = $this->cacheVersion('pasabuy_schedules');

        return Cache::remember(
            "pasabuy_schedules:uuid:v{$version}:{$uuid}",
            120,
            fn () => PasabuySchedule::with([
                'scheduleProducts' => fn ($query) => $query->where('is_disabled', false),
                'scheduleProducts.product.category',
            ])->where('uuid', $uuid)->first()
        );
    }

    public function updateStatus(string $uuid, string $status)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();
        $schedule->status = $status;
        $schedule->finished_at = $status === 'finished' ? ($schedule->finished_at ?? now()) : $schedule->finished_at;
        $schedule->save();

        $this->bumpCacheVersion('pasabuy_schedules');

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
        $schedule->pasabuy_schedule_date = $payload->pasabuy_schedule_date ?? null;
        $schedule->status = $payload->status ?? 'open';
        $schedule->save();

        $this->bumpCacheVersion('pasabuy_schedules');

        return $schedule;
    }

    public function update(string $uuid, object $payload)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        $schedule->title = $payload->title;
        $schedule->description = $payload->description ?? null;
        $schedule->pasabuy_schedule_date = $payload->pasabuy_schedule_date ?? null;
        $schedule->status = $payload->status ?? $schedule->status;
        $schedule->save();

        $this->bumpCacheVersion('pasabuy_schedules');

        return $schedule;
    }

    public function delete(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();
        $schedule->delete();

        $this->bumpCacheVersion('pasabuy_schedules');

        return $schedule;
    }

    public function findOrCreateScheduleProduct(PasabuySchedule $schedule, PasabuyProduct $product)
    {
        $scheduleProduct = PasabuyScheduleProduct::firstOrCreate(
            ['pasabuy_schedule_id' => $schedule->id, 'product_id' => $product->id],
            ['product_status' => $product->default_product_status, 'actual_price' => null, 'is_disabled' => false],
        );

        if ($scheduleProduct->wasRecentlyCreated) {
            $this->bumpCacheVersion('pasabuy_schedules');
        }

        return $scheduleProduct;
    }

    public function orders(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return $schedule->orders()->with('items.pasabuyScheduleProduct.product', 'user', 'pasabuySchedule', 'payments')->latest()->get();
    }

    public function confirmProductPrice(string $scheduleUuid, string $scheduleProductUuid, object $payload)
    {
        $scheduleProduct = PasabuyScheduleProduct::where('uuid', $scheduleProductUuid)
            ->whereHas('pasabuySchedule', fn ($query) => $query->where('uuid', $scheduleUuid))
            ->first();

        if (! $scheduleProduct) {
            return null;
        }

        $scheduleProduct->actual_price = $payload->product_status === 'out_of_stock' ? null : $payload->actual_price;
        $scheduleProduct->product_status = $payload->product_status;
        $scheduleProduct->price_confirmed_at = now();
        $scheduleProduct->save();

        $this->bumpCacheVersion('pasabuy_schedules');

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

        $rows = PasabuyOrderItem::selectRaw('pasabuy_schedule_product_id, SUM(quantity) as total_quantity')
            ->whereHas('order', fn ($query) => $query->where('pasabuy_schedule_id', $schedule->id)
                ->whereIn('status', PasabuyOrder::LIVE_STATUSES))
            ->groupBy('pasabuy_schedule_product_id')
            ->get();

        $scheduleProducts = PasabuyScheduleProduct::whereIn('id', $rows->pluck('pasabuy_schedule_product_id'))
            ->with('product.category')
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($scheduleProducts) {
            $scheduleProduct = $scheduleProducts[$row->pasabuy_schedule_product_id];
            $row->product_id = $scheduleProduct->product_id;
            $row->product = $scheduleProduct->product;

            return $row;
        });
    }

    public function productBuyers(string $scheduleUuid, string $productUuid)
    {
        $schedule = PasabuySchedule::where('uuid', $scheduleUuid)->first();
        $product = PasabuyProduct::where('uuid', $productUuid)->first();

        $scheduleProduct = $this->findScheduleProduct($schedule->id, $product->id);

        if (! $scheduleProduct) {
            return collect();
        }

        return PasabuyOrderItem::where('pasabuy_schedule_product_id', $scheduleProduct->id)
            ->whereHas('order', fn ($query) => $query->whereIn('status', PasabuyOrder::LIVE_STATUSES))
            ->with('order')
            ->get();
    }

    public function checklist(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return PasabuyScheduleChecklistItem::where('pasabuy_schedule_id', $schedule->id)
            ->with('product.category')
            ->orderBy('product_name')
            ->get();
    }

    public function findByChecklistShareToken(string $token)
    {
        return PasabuySchedule::where('checklist_share_token', $token)->first();
    }

    public function getOrCreateChecklistShareToken(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        if ($schedule->checklist_share_token) {
            return $schedule->checklist_share_token;
        }

        return $this->assignChecklistShareToken($schedule);
    }

    public function regenerateChecklistShareToken(string $uuid)
    {
        $schedule = PasabuySchedule::where('uuid', $uuid)->first();

        return $this->assignChecklistShareToken($schedule);
    }

    protected function assignChecklistShareToken(PasabuySchedule $schedule)
    {
        do {
            $token = Str::random(40);
        } while (PasabuySchedule::where('checklist_share_token', $token)->exists());

        $schedule->checklist_share_token = $token;
        $schedule->save();

        return $token;
    }

    public function toggleChecklistItem(string $scheduleUuid, string $productUuid, bool $isChecked)
    {
        $schedule = PasabuySchedule::where('uuid', $scheduleUuid)->first();
        $product = PasabuyProduct::where('uuid', $productUuid)->first();

        $item = PasabuyScheduleChecklistItem::where('pasabuy_schedule_id', $schedule->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $item) {
            return null;
        }

        $item->checked_at = $isChecked ? now() : null;
        $item->save();

        return $item->fresh('product.category');
    }

    public function syncChecklistOnClose(PasabuySchedule $schedule)
    {
        $summaryRows = $this->summary($schedule->uuid);
        $seenProductIds = [];

        $statusByProductId = PasabuyScheduleProduct::where('pasabuy_schedule_id', $schedule->id)
            ->pluck('product_status', 'product_id');

        DB::transaction(function () use ($schedule, $summaryRows, $statusByProductId, &$seenProductIds) {
            foreach ($summaryRows as $row) {
                $status = $statusByProductId[$row->product_id] ?? 'none';

                if ($status === 'out_of_stock') {
                    continue;
                }

                $totalQuantity = $status === 'b1t1' ? $row->total_quantity * 2 : $row->total_quantity;

                if ($totalQuantity <= 0) {
                    continue;
                }

                $seenProductIds[] = $row->product_id;

                $item = PasabuyScheduleChecklistItem::firstOrNew([
                    'pasabuy_schedule_id' => $schedule->id,
                    'product_id' => $row->product_id,
                ]);

                if (! $item->exists) {
                    $item->product_name = $row->product->name;
                    $item->quantity = $totalQuantity;
                    $item->checked_at = null;
                    $item->additional_quantity = 0;
                    $item->return_quantity = 0;
                } elseif (! $item->is_checked) {
                    $item->product_name = $row->product->name;
                    $item->quantity = $totalQuantity;
                    $item->additional_quantity = 0;
                    $item->return_quantity = 0;
                } else {
                    $delta = $totalQuantity - $item->quantity;
                    $item->additional_quantity = $delta > 0 ? $delta : 0;
                    $item->return_quantity = $delta < 0 ? abs($delta) : 0;
                }

                $item->save();
            }

            PasabuyScheduleChecklistItem::where('pasabuy_schedule_id', $schedule->id)
                ->whereNotIn('product_id', $seenProductIds)
                ->get()
                ->each(function (PasabuyScheduleChecklistItem $item) {
                    if (! $item->is_checked) {
                        $item->delete();

                        return;
                    }

                    $item->return_quantity = $item->quantity;
                    $item->additional_quantity = 0;
                    $item->save();
                });
        });
    }
}
