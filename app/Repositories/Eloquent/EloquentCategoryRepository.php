<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of CategoryRepositoryInterface
 */
final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Create a new category
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Update an existing category
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $category = $this->findOrFail($id);

        return $category->update($data);
    }

    /**
     * Find a category by ID
     */
    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }

    /**
     * Find a category by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * Find a category by slug
     */
    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    /**
     * Get all categories
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Category>
     */
    public function all(?array $columns = null): Collection
    {
        if ($columns !== null) {
            /** @var array<int, string> $columnArray */
            $columnArray = array_values($columns);

            return Category::query()->get($columnArray);
        }

        return Category::all();
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Category>
     */
    public function query(): Builder
    {
        return Category::query();
    }
}
