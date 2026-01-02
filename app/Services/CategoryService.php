<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CacheKey;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
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
                ->select(['id', 'name', 'slug'])
                ->orderBy('name')
                ->get()
        );
    }
}
