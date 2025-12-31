<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of TagRepositoryInterface
 */
final class EloquentTagRepository implements TagRepositoryInterface
{
    /**
     * Create a new tag
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag
    {
        return Tag::create($data);
    }

    /**
     * Update an existing tag
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $tag = $this->findOrFail($id);

        return $tag->update($data);
    }

    /**
     * Find a tag by ID
     */
    public function findById(int $id): ?Tag
    {
        return Tag::find($id);
    }

    /**
     * Find a tag by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Tag
    {
        return Tag::findOrFail($id);
    }

    /**
     * Find a tag by slug
     */
    public function findBySlug(string $slug): ?Tag
    {
        return Tag::where('slug', $slug)->first();
    }

    /**
     * Get all tags
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Tag>
     */
    public function all(?array $columns = null): Collection
    {
        if ($columns !== null) {
            /** @var array<int, string> $columnArray */
            $columnArray = array_values($columns);

            return Tag::query()->get($columnArray);
        }

        return Tag::all();
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Tag>
     */
    public function query(): Builder
    {
        return Tag::query();
    }
}
