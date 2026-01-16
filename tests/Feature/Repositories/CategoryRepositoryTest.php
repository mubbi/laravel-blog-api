<?php

declare(strict_types=1);

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

describe('CategoryRepository', function () {
    beforeEach(function () {
        $this->repository = app(CategoryRepositoryInterface::class);
    });

    describe('create', function () {
        it('creates a category', function () {
            $data = ['name' => 'Technology', 'slug' => 'technology'];

            $result = $this->repository->create($data);

            expect($result)->toBeInstanceOf(Category::class)
                ->and($result->name)->toBe('Technology')
                ->and($result->slug)->toBe('technology');
            $this->assertDatabaseHas('categories', $data);
        });
    });

    describe('findById', function () {
        it('finds category by id', function () {
            $category = Category::factory()->create();

            $result = $this->repository->findById($category->id);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($category->id);
        });

        it('returns null when category does not exist', function () {
            expect($this->repository->findById(99999))->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('finds category by id or fails', function () {
            $category = Category::factory()->create();

            $result = $this->repository->findOrFail($category->id);

            expect($result->id)->toBe($category->id);
        });

        it('throws exception when category does not exist', function () {
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('findBySlug', function () {
        it('finds category by slug', function () {
            $category = Category::factory()->create(['slug' => 'technology']);

            $result = $this->repository->findBySlug('technology');

            expect($result)->not->toBeNull()
                ->and($result->slug)->toBe('technology')
                ->and($result->id)->toBe($category->id);
        });

        it('returns null when slug does not exist', function () {
            expect($this->repository->findBySlug('non-existent'))->toBeNull();
        });
    });

    describe('update', function () {
        it('updates a category', function () {
            $category = Category::factory()->create(['name' => 'Old Name']);

            $result = $this->repository->update($category->id, ['name' => 'New Name']);

            expect($result)->toBeTrue();
            $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
        });
    });

    describe('delete', function () {
        it('deletes a category', function () {
            $category = Category::factory()->create();

            $result = $this->repository->delete($category->id);

            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        });
    });

    describe('all', function () {
        it('gets all categories', function () {
            Category::factory()->count(5)->create();

            expect($this->repository->all())->toHaveCount(5);
        });

        it('gets all categories with specific columns', function () {
            Category::factory()->create();

            $result = $this->repository->all(['id', 'name']);

            expect($result)->toHaveCount(1)
                ->and($result->first()->getAttributes())->toHaveKeys(['id', 'name']);
        });
    });

    describe('query', function () {
        it('returns query builder instance', function () {
            expect($this->repository->query())
                ->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('chains query builder methods', function () {
            Category::factory()->create(['name' => 'Tech']);
            Category::factory()->create(['name' => 'Science']);

            $result = $this->repository->query()->where('name', 'Tech')->first();

            expect($result)->not->toBeNull()
                ->and($result->name)->toBe('Tech');
        });
    });
});
