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
     * @param  CacheKey|string  $key  The cache key enum or string
     * @param  callable(): T  $callback  The callback to execute if cache miss
     * @param  int|null  $ttl  Optional TTL in seconds (overrides config default)
     * @return T
     */
    public function remember(CacheKey|string $key, callable $callback, ?int $ttl = null): mixed;

    /**
     * Forget a cache entry by key
     *
     * @param  CacheKey|string  $key  The cache key enum or string to forget
     */
    public function forget(CacheKey|string $key): void;
}
