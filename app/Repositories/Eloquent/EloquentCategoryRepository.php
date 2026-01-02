<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Eloquent implementation of CategoryRepositoryInterface
 *
 * @extends BaseEloquentRepository<Category>
 */
final class EloquentCategoryRepository extends BaseEloquentRepository implements CategoryRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Category>
     */
    protected function getModelClass(): string
    {
        return Category::class;
    }

    /**
     * Create a new category
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        /** @var Category $category */
        $category = parent::create($data);

        return $category;
    }

    /**
     * Find a category by ID
     */
    public function findById(int $id): ?Category
    {
        /** @var Category|null $category */
        $category = parent::findById($id);

        return $category;
    }

    /**
     * Find a category by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Category
    {
        /** @var Category $category */
        $category = parent::findOrFail($id);

        return $category;
    }

    /**
     * Find a category by slug
     */
    public function findBySlug(string $slug): ?Category
    {
        /** @var Category|null $category */
        $category = $this->query()->where('slug', $slug)->first();

        return $category;
    }

    /**
     * Get all categories
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, Category>
     */
    public function all(?array $columns = null): Collection
    {
        /** @var Collection<int, Category> $collection */
        $collection = parent::all($columns);

        return $collection;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Category>
     */
    public function query(): Builder
    {
        /** @var Builder<Category> $builder */
        $builder = parent::query();

        return $builder;
    }
}
