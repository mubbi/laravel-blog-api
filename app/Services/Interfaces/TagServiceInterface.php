<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Tag\CreateTagDTO;
use App\Data\Tag\UpdateTagDTO;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

interface TagServiceInterface
{
    /**
     * Get all tags from cache or database
     *
     * @return Collection<int, Tag>
     */
    public function getAllTags(): Collection;

    /**
     * Create a new tag
     */
    public function createTag(CreateTagDTO $dto): Tag;

    /**
     * Update an existing tag
     */
    public function updateTag(Tag $tag, UpdateTagDTO $dto): Tag;

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): void;
}
