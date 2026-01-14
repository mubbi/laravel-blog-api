<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CacheKey;
use App\Models\Tag;
use App\Services\CacheService;

/**
 * Observer for Tag model to handle cache invalidation
 */
final class TagObserver
{
    public function __construct(
        private readonly CacheService $cacheService
    ) {}

    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        $this->cacheService->forget(CacheKey::TAGS);
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        $this->cacheService->forget(CacheKey::TAGS);
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        $this->cacheService->forget(CacheKey::TAGS);
    }

    /**
     * Handle the Tag "restored" event.
     */
    public function restored(Tag $tag): void
    {
        $this->cacheService->forget(CacheKey::TAGS);
    }
}
