<?php

namespace App\Repositories;

use App\Interfaces\Product\ProductRepositoryInterface;
use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderItem;
use App\Models\PasabuyProduct;
use App\Traits\InteractsWithCache;
use Illuminate\Support\Facades\Cache;

class ProductRepository implements ProductRepositoryInterface
{
    use InteractsWithCache;

    public function all(object $payload)
    {
        $version = $this->cacheVersion('products');

        return Cache::remember(
            "products:all:v{$version}:".md5(json_encode($payload)),
            300,
            fn () => PasabuyProduct::filter((array) $payload)
                ->with('latestScheduleProduct', 'category')
                ->orderBy('name')
                ->paginate($payload->per_page ?? 15)
        );
    }

    public function publicCatalog(object $payload)
    {
        $version = $this->cacheVersion('products');

        return Cache::remember(
            "products:public_catalog:v{$version}:".md5(json_encode($payload)),
            300,
            fn () => PasabuyProduct::filter((array) $payload)
                ->where('is_active', true)
                ->with('category')
                ->orderBy('name')
                ->paginate($payload->per_page ?? 200)
        );
    }

    public function publicTopSelling(object $payload)
    {
        $version = $this->cacheVersion('products');
        $limit = $payload->limit ?? 10;
        $month = now()->format('Y-m');

        return Cache::remember(
            "products:public_top_selling:v{$version}:{$month}:{$limit}",
            300,
            function () use ($limit) {
                $from = now()->subMonth()->startOfMonth();

                $ranked = PasabuyOrderItem::selectRaw('pasabuy_products.id, SUM(pasabuy_order_items.quantity) as total_quantity')
                    ->join('pasabuy_schedule_products', 'pasabuy_schedule_products.id', '=', 'pasabuy_order_items.pasabuy_schedule_product_id')
                    ->join('pasabuy_products', 'pasabuy_products.id', '=', 'pasabuy_schedule_products.product_id')
                    ->where('pasabuy_products.is_active', true)
                    ->whereHas('order', fn ($query) => $query->whereHas('pasabuySchedule', fn ($q2) => $q2->where('pasabuy_schedule_date', '>=', $from))
                        ->whereIn('status', PasabuyOrder::LIVE_STATUSES))
                    ->groupBy('pasabuy_products.id')
                    ->orderByDesc('total_quantity')
                    ->limit($limit)
                    ->pluck('total_quantity', 'pasabuy_products.id');

                if ($ranked->isEmpty()) {
                    return collect();
                }

                return PasabuyProduct::with('category')
                    ->whereIn('id', $ranked->keys())
                    ->get()
                    ->sortByDesc(fn ($product) => $ranked[$product->id])
                    ->values();
            }
        );
    }

    public function findByUuid(string $uuid)
    {
        $version = $this->cacheVersion('products');

        return Cache::remember(
            "products:uuid:v{$version}:{$uuid}",
            600,
            fn () => PasabuyProduct::with('category')->where('uuid', $uuid)->first()
        );
    }

    public function publicFindByUuid(string $uuid)
    {
        $version = $this->cacheVersion('products');

        return Cache::remember(
            "products:public_uuid:v{$version}:{$uuid}",
            600,
            fn () => PasabuyProduct::with('category')->where('uuid', $uuid)->where('is_active', true)->first()
        );
    }

    public function findByUuidWithTrashed(string $uuid)
    {
        $version = $this->cacheVersion('products');

        return Cache::remember(
            "products:uuid_with_trashed:v{$version}:{$uuid}",
            600,
            fn () => PasabuyProduct::withTrashed()->with('category')->where('uuid', $uuid)->first()
        );
    }

    public function create(object $payload)
    {
        $product = new PasabuyProduct;
        $product->name = $payload->name;
        $product->description = $payload->description ?? null;
        $product->image_path = $payload->image_path ?? null;
        $product->is_active = $payload->is_active ?? true;
        $product->category_id = $payload->category_id ?? null;
        $product->default_price = $payload->default_price;
        $product->default_product_status = $payload->default_product_status ?? 'none';
        $product->save();

        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $product;
    }

    public function update(string $uuid, object $payload)
    {
        $product = PasabuyProduct::where('uuid', $uuid)->first();

        $product->name = $payload->name;
        $product->description = $payload->description ?? null;
        if (isset($payload->image_path)) {
            $product->image_path = $payload->image_path;
        }
        $product->is_active = $payload->is_active ?? $product->is_active;
        $product->category_id = $payload->category_id ?? null;
        $product->default_price = $payload->default_price;
        $product->default_product_status = $payload->default_product_status ?? $product->default_product_status;
        $product->save();

        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $product;
    }

    public function delete(string $uuid)
    {
        $product = PasabuyProduct::where('uuid', $uuid)->first();
        $product->delete();

        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $product;
    }

    public function topSelling(object $payload)
    {
        $year = $payload->year ?? now()->year;
        $month = $payload->month ?? now()->month;

        return PasabuyOrderItem::selectRaw('pasabuy_products.id, pasabuy_products.uuid, pasabuy_products.name, SUM(pasabuy_order_items.quantity) as total_quantity')
            ->join('pasabuy_schedule_products', 'pasabuy_schedule_products.id', '=', 'pasabuy_order_items.pasabuy_schedule_product_id')
            ->join('pasabuy_products', 'pasabuy_products.id', '=', 'pasabuy_schedule_products.product_id')
            ->whereHas('order', fn ($query) => $query->whereHas('pasabuySchedule', fn ($q2) => $q2->where('user_id', $payload->seller_id)
                ->whereYear('pasabuy_schedule_date', $year)
                ->whereMonth('pasabuy_schedule_date', $month))
                ->whereIn('status', PasabuyOrder::LIVE_STATUSES))
            ->groupBy('pasabuy_products.id', 'pasabuy_products.uuid', 'pasabuy_products.name')
            ->orderByDesc('total_quantity')
            ->limit($payload->limit ?? 5)
            ->get();
    }
}
