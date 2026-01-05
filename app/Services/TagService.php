<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CreateTagDTO;
use App\Data\UpdateTagDTO;
use App\Enums\CacheKey;
use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Create a new tag
     */
    public function createTag(CreateTagDTO $dto): Tag
    {
        return DB::transaction(function () use ($dto): Tag {
            $tag = $this->tagRepository->create($dto->toArray());
            $this->cacheService->forget(CacheKey::TAGS);

            return $tag;
        });
    }

    /**
     * Update an existing tag
     */
    public function updateTag(Tag $tag, UpdateTagDTO $dto): Tag
    {
        return DB::transaction(function () use ($tag, $dto): Tag {
            $updateData = $dto->toArray();
            if ($updateData !== []) {
                $this->tagRepository->update($tag->id, $updateData);
                $tag->refresh();
            }
            $this->cacheService->forget(CacheKey::TAGS);

            return $tag;
        });
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): void
    {
        DB::transaction(function () use ($tag): void {
            // Delete the tag itself
            $tag->delete();
            $this->cacheService->forget(CacheKey::TAGS);
        });
    }
}
