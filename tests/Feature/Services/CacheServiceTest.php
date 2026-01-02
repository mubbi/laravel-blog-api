<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('CacheService', function () {
    beforeEach(function () {
        $this->service = app(CacheService::class);
        Cache::flush();
    });

    describe('remember', function () {
        it('caches and returns value', function () {
            // Arrange
            $callback = fn () => 'cached_value';

            // Act
            $result1 = $this->service->remember(CacheKey::CATEGORIES, $callback);
            $result2 = $this->service->remember(CacheKey::CATEGORIES, $callback);

            // Assert
            expect($result1)->toBe('cached_value');
            expect($result2)->toBe('cached_value');
            expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
        });

        it('executes callback only once when cached', function () {
            // Arrange
            $executionCount = 0;
            $callback = function () use (&$executionCount) {
                $executionCount++;

                return 'value';
            };

            // Act
            $this->service->remember(CacheKey::CATEGORIES, $callback);
            $this->service->remember(CacheKey::CATEGORIES, $callback);
            $this->service->remember(CacheKey::CATEGORIES, $callback);

            // Assert
            expect($executionCount)->toBe(1);
        });

        it('caches different keys separately', function () {
            // Arrange
            $callback1 = fn () => 'value1';
            $callback2 = fn () => 'value2';

            // Act
            $result1 = $this->service->remember(CacheKey::CATEGORIES, $callback1);
            $result2 = $this->service->remember(CacheKey::TAGS, $callback2);

            // Assert
            expect($result1)->toBe('value1');
            expect($result2)->toBe('value2');
            expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
            expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();
        });
    });

    describe('forget', function () {
        it('removes cached value', function () {
            // Arrange
            Cache::put(CacheKey::CATEGORIES->value, 'value', 3600);
            expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

            // Act
            $this->service->forget(CacheKey::CATEGORIES);

            // Assert
            expect(Cache::has(CacheKey::CATEGORIES->value))->toBeFalse();
        });

        it('does not throw error when key does not exist', function () {
            // Act & Assert - Should not throw
            $this->service->forget(CacheKey::CATEGORIES);
            expect(true)->toBeTrue(); // If we get here, no exception was thrown
        });
    });
});
