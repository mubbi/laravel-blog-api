<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CacheKey;
use App\Models\Category;
use App\Services\CacheService;

/**
 * Observer for Category model to handle cache invalidation
 */
final class CategoryObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        $this->cacheService->forget(CacheKey::CATEGORIES);
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $this->cacheService->forget(CacheKey::CATEGORIES);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->cacheService->forget(CacheKey::CATEGORIES);
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        $this->cacheService->forget(CacheKey::CATEGORIES);
    }
}
