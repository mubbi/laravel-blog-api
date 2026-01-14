<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CreateCategoryDTO;
use App\Data\DeleteCategoryDTO;
use App\Data\UpdateCategoryDTO;
use App\Enums\CacheKey;
use App\Events\Category\CategoryCreatedEvent;
use App\Events\Category\CategoryDeletedEvent;
use App\Events\Category\CategoryUpdatedEvent;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Interfaces\CategoryServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Get all categories from cache or database
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection
    {
        return $this->cacheService->remember(
            CacheKey::CATEGORIES,
            fn () => $this->categoryRepository->query()
                ->select(['id', 'name', 'slug', 'parent_id'])
                ->with('parent')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Create a new category
     */
    public function createCategory(CreateCategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto): Category {
            $category = $this->categoryRepository->create($dto->toArray());
            $this->cacheService->forget(CacheKey::CATEGORIES);

            Event::dispatch(new CategoryCreatedEvent($category));

            return $category;
        });
    }

    /**
     * Update an existing category
     */
    public function updateCategory(Category $category, UpdateCategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($category, $dto): Category {
            $updateData = $dto->toArray();
            if ($updateData !== []) {
                $this->categoryRepository->update($category->id, $updateData);
                $category->refresh();
            }
            $this->cacheService->forget(CacheKey::CATEGORIES);

            Event::dispatch(new CategoryUpdatedEvent($category));

            return $category;
        });
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Category $category, DeleteCategoryDTO $dto): void
    {
        DB::transaction(function () use ($category, $dto): void {
            if ($dto->deleteChildren) {
                $this->deleteDescendants($category->id);
            } else {
                $this->moveChildrenToParent($category);
            }

            Event::dispatch(new CategoryDeletedEvent($category));

            $category->delete();
            $this->cacheService->forget(CacheKey::CATEGORIES);
        });
    }

    /**
     * Delete all descendant categories efficiently
     */
    private function deleteDescendants(int $categoryId): void
    {
        $descendantIds = $this->getDescendantIds($categoryId);
        if ($descendantIds !== []) {
            $this->categoryRepository->query()->whereIn('id', $descendantIds)->delete();
        }
    }

    /**
     * Get all descendant category IDs recursively
     *
     * @return array<int>
     */
    private function getDescendantIds(int $categoryId): array
    {
        /** @var array<int> $descendantIds */
        $descendantIds = [];
        /** @var array<int> $children */
        $children = $this->categoryRepository->query()
            ->where('parent_id', $categoryId)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        foreach ($children as $childId) {
            $descendantIds[] = $childId;
            /** @var array<int> $merged */
            $merged = array_merge($descendantIds, $this->getDescendantIds($childId));
            $descendantIds = $merged;
        }

        return $descendantIds;
    }

    /**
     * Move children to the category's parent (or make them root)
     */
    private function moveChildrenToParent(Category $category): void
    {
        $this->categoryRepository->query()
            ->where('parent_id', $category->id)
            ->update(['parent_id' => $category->parent_id]);
    }
}
