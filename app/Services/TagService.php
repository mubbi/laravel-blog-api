<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CreateTagDTO;
use App\Data\UpdateTagDTO;
use App\Enums\CacheKey;
use App\Events\Tag\TagCreatedEvent;
use App\Events\Tag\TagDeletedEvent;
use App\Events\Tag\TagUpdatedEvent;
use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

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

            Event::dispatch(new TagCreatedEvent($tag));

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

            Event::dispatch(new TagUpdatedEvent($tag));

            return $tag;
        });
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): void
    {
        DB::transaction(function () use ($tag): void {
            Event::dispatch(new TagDeletedEvent($tag));

            // Delete the tag itself
            $tag->delete();
            $this->cacheService->forget(CacheKey::TAGS);
        });
    }
}
