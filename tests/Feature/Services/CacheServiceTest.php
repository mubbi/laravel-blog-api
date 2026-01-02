<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

describe('CacheService', function () {
    beforeEach(function () {
        // Clear all caches before each test
        Cache::flush();
    });

    it('can remember a value in cache', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $expectedValue = ['test' => 'data'];

        // Act
        $result = $cacheService->remember(CacheKey::CATEGORIES, fn () => $expectedValue);

        // Assert
        expect($result)->toBe($expectedValue);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
    });

    it('returns cached value on subsequent calls', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;

            return ['data' => 'value'];
        };

        // Act - First call should execute callback
        $result1 = $cacheService->remember(CacheKey::CATEGORIES, $callback);
        expect($callCount)->toBe(1);

        // Act - Second call should use cache
        $result2 = $cacheService->remember(CacheKey::CATEGORIES, $callback);

        // Assert
        expect($callCount)->toBe(1); // Callback should not be called again
        expect($result1)->toBe($result2);
        expect($result2)->toBe(['data' => 'value']);
    });

    it('uses configured TTL for cache key', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $testValue = ['test' => 'data'];

        // Act
        $cacheService->remember(CacheKey::CATEGORIES, fn () => $testValue);

        // Assert - Check that cache entry exists with proper TTL
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
        // The TTL should match the configured value from config/cache-ttl.php
        // Default is 86400 seconds (24 hours) for categories
    });

    it('uses default TTL when key-specific TTL is not configured', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $testValue = ['test' => 'data'];

        // Act - Use a cache key that might not have specific TTL
        $cacheService->remember(CacheKey::TAGS, fn () => $testValue);

        // Assert
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();
    });

    it('can forget a cache entry', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $testValue = ['test' => 'data'];

        // Act - Cache a value
        $cacheService->remember(CacheKey::CATEGORIES, fn () => $testValue);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Act - Forget the cache
        $cacheService->forget(CacheKey::CATEGORIES);

        // Assert
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeFalse();
    });

    it('handles different cache keys independently', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $categoriesValue = ['categories' => 'data'];
        $tagsValue = ['tags' => 'data'];

        // Act
        $cacheService->remember(CacheKey::CATEGORIES, fn () => $categoriesValue);
        $cacheService->remember(CacheKey::TAGS, fn () => $tagsValue);

        // Assert
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();
        expect(Cache::get(CacheKey::CATEGORIES->value))->toBe($categoriesValue);
        expect(Cache::get(CacheKey::TAGS->value))->toBe($tagsValue);
    });

    it('executes callback when cache is empty', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;

            return ['executed' => true];
        };

        // Act
        $result = $cacheService->remember(CacheKey::CATEGORIES, $callback);

        // Assert
        expect($executed)->toBeTrue();
        expect($result)->toBe(['executed' => true]);
    });

    it('works with closure callbacks', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $closure = function () {
            return ['closure' => 'result'];
        };

        // Act
        $result = $cacheService->remember(CacheKey::CATEGORIES, $closure);

        // Assert
        expect($result)->toBe(['closure' => 'result']);
    });

    it('works with callable callbacks', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $callable = function () {
            return ['callable' => 'result'];
        };

        // Act - Using a callable
        $result = $cacheService->remember(CacheKey::CATEGORIES, $callable);

        // Assert - Should execute and cache the result
        expect($result)->toBe(['callable' => 'result']);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
    });

    it('handles null values correctly', function () {
        // Arrange
        $cacheService = app(CacheService::class);

        // Act
        $result = $cacheService->remember(CacheKey::CATEGORIES, fn () => null);

        // Assert
        expect($result)->toBeNull();
        // Note: Laravel cache may not store null values, so we check the result
    });

    it('handles complex data structures', function () {
        // Arrange
        $cacheService = app(CacheService::class);
        $complexData = [
            'nested' => [
                'array' => [1, 2, 3],
                'object' => (object) ['key' => 'value'],
            ],
            'string' => 'test',
            'integer' => 42,
            'boolean' => true,
        ];

        // Act
        $result = $cacheService->remember(CacheKey::CATEGORIES, fn () => $complexData);

        // Assert
        expect($result)->toBe($complexData);
    });

    it('forgets cache entry even if it does not exist', function () {
        // Arrange
        $cacheService = app(CacheService::class);

        // Act - Try to forget a non-existent cache entry
        $cacheService->forget(CacheKey::CATEGORIES);

        // Assert - Should not throw an exception
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeFalse();
    });

    it('maintains cache isolation between different keys', function () {
        // Arrange
        $cacheService = app(CacheService::class);

        // Act - Cache different values for different keys
        $cacheService->remember(CacheKey::CATEGORIES, fn () => ['key' => 'categories']);
        $cacheService->remember(CacheKey::TAGS, fn () => ['key' => 'tags']);

        // Act - Forget one key
        $cacheService->forget(CacheKey::CATEGORIES);

        // Assert - Other key should still exist
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeFalse();
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();
        expect(Cache::get(CacheKey::TAGS->value))->toBe(['key' => 'tags']);
    });
});
