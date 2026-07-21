<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait InteractsWithCache
{
    protected function cacheVersion(string $namespace)
    {
        return Cache::rememberForever("cache_version:{$namespace}", fn () => 1);
    }

    protected function bumpCacheVersion(string $namespace)
    {
        Cache::forever("cache_version:{$namespace}", $this->cacheVersion($namespace) + 1);
    }
}
