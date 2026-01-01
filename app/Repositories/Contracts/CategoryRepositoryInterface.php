<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Category repository interface
 */
interface CategoryRepositoryInterface
{
    /**
     * Create a new category
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category;

    /**
     * Update an existing category
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a category by ID
     */
    public function findById(int $id): ?Category;

    /**
     * Find a category by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Category;

    /**
     * Find a category by slug
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Get all categories
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Category>
     */
    public function all(?array $columns = null): Collection;

    /**
     * Get a query builder instance
     *
     * @return Builder<Category>
     */
    public function query(): Builder;
}
