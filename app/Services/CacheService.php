<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CacheKey;
use App\Services\Interfaces\CacheServiceInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Cache service for managing application cache operations
 * Follows SOLID principles - single responsibility: cache management only
 */
final class CacheService implements CacheServiceInterface
{
    /**
     * Get TTL for a specific cache key
     *
     * @param  CacheKey  $key  The cache key enum
     * @return int TTL in seconds
     */
    private function getTtl(CacheKey $key): int
    {
        /** @var array<string, int> $config */
        $config = config('cache-ttl.keys', []);
        $keyValue = $key->value;

        /** @var int $defaultTtl */
        $defaultTtl = config('cache-ttl.default', 86400);

        return $config[$keyValue] ?? $defaultTtl;
    }

    /**
     * Remember a value in cache, or execute callback if not cached
     *
     * @template T
     *
     * @param  CacheKey  $key  The cache key enum
     * @param  callable(): T  $callback  The callback to execute if cache miss
     * @return T
     */
    public function remember(CacheKey $key, callable $callback): mixed
    {
        /** @var \Closure(): T $closure */
        $closure = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);

        return Cache::remember(
            $key->value,
            $this->getTtl($key),
            $closure
        );
    }

    /**
     * Forget a cache entry by key
     *
     * @param  CacheKey  $key  The cache key to forget
     */
    public function forget(CacheKey $key): void
    {
        Cache::forget($key->value);
    }
}
