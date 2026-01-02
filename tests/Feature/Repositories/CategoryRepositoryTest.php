<?php

declare(strict_types=1);

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CategoryRepository', function () {
    beforeEach(function () {
        $this->repository = app(CategoryRepositoryInterface::class);
    });

    describe('create', function () {
        it('can create a category', function () {
            // Arrange
            $data = [
                'name' => 'Technology',
                'slug' => 'technology',
            ];

            // Act
            $result = $this->repository->create($data);

            // Assert
            expect($result)->toBeInstanceOf(Category::class);
            expect($result->name)->toBe('Technology');
            expect($result->slug)->toBe('technology');
            $this->assertDatabaseHas('categories', $data);
        });
    });

    describe('findById', function () {
        it('can find category by id', function () {
            // Arrange
            $category = Category::factory()->create();

            // Act
            $result = $this->repository->findById($category->id);

            // Assert
            expect($result)->not->toBeNull();
            expect($result->id)->toBe($category->id);
        });

        it('returns null when category does not exist', function () {
            // Act
            $result = $this->repository->findById(99999);

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('can find category by id or fail', function () {
            // Arrange
            $category = Category::factory()->create();

            // Act
            $result = $this->repository->findOrFail($category->id);

            // Assert
            expect($result->id)->toBe($category->id);
        });

        it('throws exception when category does not exist', function () {
            // Act & Assert
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('findBySlug', function () {
        it('can find category by slug', function () {
            // Arrange
            $category = Category::factory()->create(['slug' => 'technology']);

            // Act
            $result = $this->repository->findBySlug('technology');

            // Assert
            expect($result)->not->toBeNull();
            expect($result->slug)->toBe('technology');
            expect($result->id)->toBe($category->id);
        });

        it('returns null when slug does not exist', function () {
            // Act
            $result = $this->repository->findBySlug('non-existent');

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('update', function () {
        it('can update a category', function () {
            // Arrange
            $category = Category::factory()->create(['name' => 'Old Name']);

            // Act
            $result = $this->repository->update($category->id, ['name' => 'New Name']);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseHas('categories', [
                'id' => $category->id,
                'name' => 'New Name',
            ]);
        });
    });

    describe('delete', function () {
        it('can delete a category', function () {
            // Arrange
            $category = Category::factory()->create();

            // Act
            $result = $this->repository->delete($category->id);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        });
    });

    describe('all', function () {
        it('can get all categories', function () {
            // Arrange
            Category::factory()->count(5)->create();

            // Act
            $result = $this->repository->all();

            // Assert
            expect($result)->toHaveCount(5);
        });

        it('can get all categories with specific columns', function () {
            // Arrange
            Category::factory()->create();

            // Act
            $result = $this->repository->all(['id', 'name']);

            // Assert
            expect($result)->toHaveCount(1);
            expect($result->first()->getAttributes())->toHaveKeys(['id', 'name']);
        });
    });

    describe('query', function () {
        it('returns query builder instance', function () {
            // Act
            $result = $this->repository->query();

            // Assert
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('can chain query builder methods', function () {
            // Arrange
            Category::factory()->create(['name' => 'Tech']);
            Category::factory()->create(['name' => 'Science']);

            // Act
            $result = $this->repository->query()
                ->where('name', 'Tech')
                ->first();

            // Assert
            expect($result)->not->toBeNull();
            expect($result->name)->toBe('Tech');
        });
    });
});
