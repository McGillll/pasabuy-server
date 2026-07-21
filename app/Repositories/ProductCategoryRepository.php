<?php

namespace App\Repositories;

use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use App\Models\ProductCategory;
use App\Traits\InteractsWithCache;
use Illuminate\Support\Facades\Cache;

class ProductCategoryRepository implements ProductCategoryRepositoryInterface
{
    use InteractsWithCache;

    public function all(object $payload)
    {
        $version = $this->cacheVersion('product_categories');

        return Cache::remember(
            "product_categories:all:v{$version}:".md5(json_encode($payload)),
            300,
            fn () => ProductCategory::filter((array) $payload)
                ->orderBy('name')
                ->paginate($payload->per_page ?? 15)
        );
    }

    public function findByUuid(string $uuid)
    {
        $version = $this->cacheVersion('product_categories');

        return Cache::remember(
            "product_categories:uuid:v{$version}:{$uuid}",
            600,
            fn () => ProductCategory::where('uuid', $uuid)->first()
        );
    }

    public function create(object $payload)
    {
        $category = new ProductCategory;
        $category->name = $payload->name;
        $category->description = $payload->description ?? null;
        $category->is_active = $payload->is_active ?? true;
        $category->save();

        $this->bumpCacheVersion('product_categories');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $category;
    }

    public function update(string $uuid, object $payload)
    {
        $category = ProductCategory::where('uuid', $uuid)->first();

        $category->name = $payload->name;
        $category->description = $payload->description ?? null;
        $category->is_active = $payload->is_active ?? $category->is_active;
        $category->save();

        $this->bumpCacheVersion('product_categories');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $category;
    }

    public function delete(string $uuid)
    {
        $category = ProductCategory::where('uuid', $uuid)->first();
        $category->delete();

        $this->bumpCacheVersion('product_categories');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $category;
    }
}
