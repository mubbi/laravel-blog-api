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
     * @param  CacheKey|string  $key  The cache key enum or string
     * @return int TTL in seconds
     */
    private function getTtl(CacheKey|string $key): int
    {
        $keyValue = $key instanceof CacheKey ? $key->value : $key;

        // For dynamic keys (with prefixes), extract base key
        $baseKey = $this->extractBaseKey($keyValue);

        /** @var array<string, int> $config */
        $config = config('cache-ttl.keys', []);

        /** @var int $defaultTtl */
        $defaultTtl = config('cache-ttl.default', 86400);

        return $config[$baseKey] ?? $defaultTtl;
    }

    /**
     * Extract base key from dynamic cache key
     * e.g., 'article:slug:my-article' -> 'article_by_slug'
     *
     * @param  string  $keyValue  The cache key string
     * @return string Base key for TTL lookup
     */
    private function extractBaseKey(string $keyValue): string
    {
        // Map cache key prefixes to config keys
        if (str_starts_with($keyValue, CacheKey::ARTICLE_BY_SLUG->value)) {
            return 'article_by_slug';
        }
        if (str_starts_with($keyValue, CacheKey::ARTICLE_BY_ID->value)) {
            return 'article_by_id';
        }
        if (str_starts_with($keyValue, CacheKey::USER_ROLES->value)) {
            return 'user_roles';
        }
        if (str_starts_with($keyValue, CacheKey::USER_PERMISSIONS->value)) {
            return 'user_permissions';
        }

        return $keyValue;
    }

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
    public function remember(CacheKey|string $key, callable $callback, ?int $ttl = null): mixed
    {
        /** @var \Closure(): T $closure */
        $closure = $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback);

        $keyValue = $key instanceof CacheKey ? $key->value : $key;
        $cacheTtl = $ttl ?? $this->getTtl($key);

        return Cache::remember(
            $keyValue,
            $cacheTtl,
            $closure
        );
    }

    /**
     * Forget a cache entry by key
     *
     * @param  CacheKey|string  $key  The cache key enum or string to forget
     */
    public function forget(CacheKey|string $key): void
    {
        $keyValue = $key instanceof CacheKey ? $key->value : $key;
        Cache::forget($keyValue);
    }
}
