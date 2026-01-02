<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CacheKey;
use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class TagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get all tags from cache or database
     *
     * @return Collection<int, Tag>
     */
    public function getAllTags(): Collection
    {
        return $this->cacheService->remember(
            CacheKey::TAGS,
            fn () => $this->tagRepository->query()
                ->select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->get()
        );
    }
}
