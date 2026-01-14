<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TagRepository', function () {
    beforeEach(function () {
        $this->repository = app(TagRepositoryInterface::class);
    });

    describe('create', function () {
        it('can create a tag', function () {
            // Arrange
            $data = [
                'name' => 'PHP',
                'slug' => 'php',
            ];

            // Act
            $result = $this->repository->create($data);

            // Assert
            expect($result)->toBeInstanceOf(Tag::class);
            expect($result->name)->toBe('PHP');
            expect($result->slug)->toBe('php');
            $this->assertDatabaseHas('tags', $data);
        });
    });

    describe('findById', function () {
        it('can find tag by id', function () {
            // Arrange
            $tag = Tag::factory()->create();

            // Act
            $result = $this->repository->findById($tag->id);

            // Assert
            expect($result)->not->toBeNull();
            expect($result->id)->toBe($tag->id);
        });

        it('returns null when tag does not exist', function () {
            // Act
            $result = $this->repository->findById(99999);

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('can find tag by id or fail', function () {
            // Arrange
            $tag = Tag::factory()->create();

            // Act
            $result = $this->repository->findOrFail($tag->id);

            // Assert
            expect($result->id)->toBe($tag->id);
        });

        it('throws exception when tag does not exist', function () {
            // Act & Assert
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('findBySlug', function () {
        it('can find tag by slug', function () {
            // Arrange
            $tag = Tag::factory()->create(['slug' => 'php']);

            // Act
            $result = $this->repository->findBySlug('php');

            // Assert
            expect($result)->not->toBeNull();
            expect($result->slug)->toBe('php');
            expect($result->id)->toBe($tag->id);
        });

        it('returns null when slug does not exist', function () {
            // Act
            $result = $this->repository->findBySlug('non-existent');

            // Assert
            expect($result)->toBeNull();
        });
    });

    describe('update', function () {
        it('can update a tag', function () {
            // Arrange
            $tag = Tag::factory()->create(['name' => 'Old Name']);

            // Act
            $result = $this->repository->update($tag->id, ['name' => 'New Name']);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseHas('tags', [
                'id' => $tag->id,
                'name' => 'New Name',
            ]);
        });
    });

    describe('delete', function () {
        it('can delete a tag', function () {
            // Arrange
            $tag = Tag::factory()->create();

            // Act
            $result = $this->repository->delete($tag->id);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        });
    });

    describe('all', function () {
        it('can get all tags', function () {
            // Arrange
            Tag::factory()->count(5)->create();

            // Act
            $result = $this->repository->all();

            // Assert
            expect($result)->toHaveCount(5);
        });

        it('can get all tags with specific columns', function () {
            // Arrange
            Tag::factory()->create();

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
            Tag::factory()->create(['name' => 'PHP']);
            Tag::factory()->create(['name' => 'Laravel']);

            // Act
            $result = $this->repository->query()
                ->where('name', 'PHP')
                ->first();

            // Assert
            expect($result)->not->toBeNull();
            expect($result->name)->toBe('PHP');
        });
    });
});
