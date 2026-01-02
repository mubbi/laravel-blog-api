<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('CategoryService', function () {
    beforeEach(function () {
        $this->service = app(CategoryService::class);
    });

    it('can get all categories from database', function () {
        // Arrange
        Category::factory()->count(5)->create();

        // Act
        $categories = $this->service->getAllCategories();

        // Assert
        expect($categories)->toHaveCount(5);
        expect($categories->first())->toBeInstanceOf(Category::class);
    });

    it('returns categories ordered by name', function () {
        // Arrange
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Apple']);
        Category::factory()->create(['name' => 'Banana']);

        // Act
        $categories = $this->service->getAllCategories();

        // Assert
        expect($categories->pluck('name')->toArray())->toBe(['Apple', 'Banana', 'Zebra']);
    });

    it('caches categories', function () {
        // Arrange
        Cache::forget(CacheKey::CATEGORIES->value);
        Category::factory()->count(3)->create();

        // Act - First call
        $categories1 = $this->service->getAllCategories();

        // Assert - Should be cached
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Act - Second call should use cache
        $categories2 = $this->service->getAllCategories();

        // Assert
        expect($categories1->count())->toBe($categories2->count());
    });

    it('returns only id, name, and slug fields', function () {
        // Arrange
        $category = Category::factory()->create();

        // Act
        $categories = $this->service->getAllCategories();

        // Assert
        $firstCategory = $categories->first();
        expect($firstCategory->getAttributes())->toHaveKeys(['id', 'name', 'slug']);
    });

    it('returns empty collection when no categories exist', function () {
        // Act
        $categories = $this->service->getAllCategories();

        // Assert
        expect($categories)->toHaveCount(0);
        expect($categories)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });
});
