<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Category\CreateCategoryDTO;
use App\Data\Category\DeleteCategoryDTO;
use App\Data\Category\UpdateCategoryDTO;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryServiceInterface
{
    /**
     * Get all categories from cache or database
     *
     * @return Collection<int, Category>
     */
    public function getAllCategories(): Collection;

    /**
     * Create a new category
     */
    public function createCategory(CreateCategoryDTO $dto): Category;

    /**
     * Update an existing category
     */
    public function updateCategory(Category $category, UpdateCategoryDTO $dto): Category;

    /**
     * Delete a category
     */
    public function deleteCategory(Category $category, DeleteCategoryDTO $dto): void;
}
