<?php

declare(strict_types=1);

use App\Data\CreateCategoryDTO;
use App\Data\DeleteCategoryDTO;
use App\Data\UpdateCategoryDTO;
use App\Enums\CacheKey;
use App\Events\Category\CategoryCreatedEvent;
use App\Events\Category\CategoryDeletedEvent;
use App\Events\Category\CategoryUpdatedEvent;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('CategoryService', function () {
    beforeEach(function () {
        $this->service = app(CategoryService::class);
    });

    it('gets all categories from database', function () {
        Category::factory()->count(5)->create();

        $categories = $this->service->getAllCategories();

        expect($categories)->toHaveCount(5)
            ->and($categories->first())->toBeInstanceOf(Category::class);
    });

    it('returns categories ordered by name', function () {
        Category::factory()->create(['name' => 'Zebra']);
        Category::factory()->create(['name' => 'Apple']);
        Category::factory()->create(['name' => 'Banana']);

        $categories = $this->service->getAllCategories();

        expect($categories->pluck('name')->toArray())->toBe(['Apple', 'Banana', 'Zebra']);
    });

    it('caches categories', function () {
        Cache::forget(CacheKey::CATEGORIES->value);
        Category::factory()->count(3)->create();

        $categories1 = $this->service->getAllCategories();
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        $categories2 = $this->service->getAllCategories();
        expect($categories1->count())->toBe($categories2->count());
    });

    it('returns only id, name, and slug fields', function () {
        Category::factory()->create();

        $categories = $this->service->getAllCategories();

        expect($categories->first()->getAttributes())->toHaveKeys(['id', 'name', 'slug']);
    });

    it('returns empty collection when no categories exist', function () {
        $categories = $this->service->getAllCategories();

        expect($categories)->toHaveCount(0)
            ->and($categories)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    describe('createCategory', function () {
        it('creates a category and dispatches event', function () {
            Event::fake([CategoryCreatedEvent::class]);
            $dto = new CreateCategoryDTO('Test Category', 'test-category', null);

            $category = $this->service->createCategory($dto);

            expect($category->name)->toBe('Test Category')
                ->and($category->slug)->toBe('test-category');
            Event::assertDispatched(CategoryCreatedEvent::class, fn ($event) => $event->category->id === $category->id);
        });
    });

    describe('updateCategory', function () {
        it('updates a category and dispatches event', function () {
            Event::fake([CategoryUpdatedEvent::class]);
            $category = Category::factory()->create(['name' => 'Old Name']);
            $dto = new UpdateCategoryDTO('New Name', null, null, false);

            $updatedCategory = $this->service->updateCategory($category, $dto);

            expect($updatedCategory->name)->toBe('New Name');
            Event::assertDispatched(CategoryUpdatedEvent::class, fn ($event) => $event->category->id === $category->id);
        });
    });

    describe('deleteCategory', function () {
        it('deletes a category and dispatches event', function () {
            Event::fake([CategoryDeletedEvent::class]);
            $category = Category::factory()->create();
            $dto = new DeleteCategoryDTO(false);

            $this->service->deleteCategory($category, $dto);

            $this->assertDatabaseMissing('categories', ['id' => $category->id]);
            Event::assertDispatched(CategoryDeletedEvent::class, fn ($event) => $event->category->id === $category->id);
        });
    });
});
