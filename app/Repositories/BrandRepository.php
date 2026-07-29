<?php

namespace App\Repositories;

use App\Interfaces\Brand\BrandRepositoryInterface;
use App\Models\Brand;
use App\Traits\InteractsWithCache;
use Illuminate\Support\Facades\Cache;

class BrandRepository implements BrandRepositoryInterface
{
    use InteractsWithCache;

    public function all(object $payload)
    {
        $version = $this->cacheVersion('brands');

        return Cache::remember(
            "brands:all:v{$version}:".md5(json_encode($payload)),
            300,
            fn () => Brand::filter((array) $payload)
                ->orderBy('name')
                ->paginate($payload->per_page ?? 15)
        );
    }

    public function findByUuid(string $uuid)
    {
        $version = $this->cacheVersion('brands');

        return Cache::remember(
            "brands:uuid:v{$version}:{$uuid}",
            600,
            fn () => Brand::where('uuid', $uuid)->first()
        );
    }

    public function create(object $payload)
    {
        $brand = new Brand;
        $brand->name = $payload->name;
        $brand->logo_url = $payload->logo_url ?? null;
        $brand->is_active = $payload->is_active ?? true;
        $brand->save();

        $this->bumpCacheVersion('brands');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $brand;
    }

    public function update(string $uuid, object $payload)
    {
        $brand = Brand::where('uuid', $uuid)->first();

        $brand->name = $payload->name;
        $brand->logo_url = $payload->logo_url ?? $brand->logo_url;
        $brand->is_active = $payload->is_active ?? $brand->is_active;
        $brand->save();

        $this->bumpCacheVersion('brands');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $brand;
    }

    public function delete(string $uuid)
    {
        $brand = Brand::where('uuid', $uuid)->first();
        $brand->delete();

        $this->bumpCacheVersion('brands');
        $this->bumpCacheVersion('products');
        $this->bumpCacheVersion('pasabuy_schedules');

        return $brand;
    }
}
