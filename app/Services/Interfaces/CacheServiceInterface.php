<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Enums\CacheKey;

interface CacheServiceInterface
{
    /**
     * Remember a value in cache, or execute callback if not cached
     *
     * @template T
     *
     * @param  CacheKey  $key  The cache key enum
     * @param  callable(): T  $callback  The callback to execute if cache miss
     * @return T
     */
    public function remember(CacheKey $key, callable $callback): mixed;

    /**
     * Forget a cache entry by key
     *
     * @param  CacheKey  $key  The cache key to forget
     */
    public function forget(CacheKey $key): void;
}
