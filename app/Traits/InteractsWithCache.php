<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    protected function etagFor(string $namespace, object $payload): string
    {
        return '"'.$namespace.'-v'.$this->cacheVersion($namespace).'-'.md5(json_encode($payload)).'"';
    }

    protected function notModified(Request $request, string $etag): ?Response
    {
        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304)->header('ETag', $etag);
        }

        return null;
    }
}
