<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tag repository interface
 */
interface TagRepositoryInterface
{
    /**
     * Create a new tag
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag;

    /**
     * Update an existing tag
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a tag by ID
     */
    public function findById(int $id): ?Tag;

    /**
     * Find a tag by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Tag;

    /**
     * Find a tag by slug
     */
    public function findBySlug(string $slug): ?Tag;

    /**
     * Get all tags
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Tag>
     */
    public function all(?array $columns = null): Collection;

    /**
     * Get a query builder instance
     *
     * @return Builder<Tag>
     */
    public function query(): Builder;
}
