<?php

declare(strict_types=1);

use App\Models\Tag;
use App\Repositories\Contracts\TagRepositoryInterface;

describe('TagRepository', function () {
    beforeEach(function () {
        $this->repository = app(TagRepositoryInterface::class);
    });

    describe('create', function () {
        it('creates a tag', function () {
            $data = ['name' => 'PHP', 'slug' => 'php'];

            $result = $this->repository->create($data);

            expect($result)->toBeInstanceOf(Tag::class)
                ->and($result->name)->toBe('PHP')
                ->and($result->slug)->toBe('php');
            $this->assertDatabaseHas('tags', $data);
        });
    });

    describe('findById', function () {
        it('finds tag by id', function () {
            $tag = Tag::factory()->create();

            $result = $this->repository->findById($tag->id);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($tag->id);
        });

        it('returns null when tag does not exist', function () {
            expect($this->repository->findById(99999))->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('finds tag by id or fails', function () {
            $tag = Tag::factory()->create();

            $result = $this->repository->findOrFail($tag->id);

            expect($result->id)->toBe($tag->id);
        });

        it('throws exception when tag does not exist', function () {
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('findBySlug', function () {
        it('finds tag by slug', function () {
            $tag = Tag::factory()->create(['slug' => 'php']);

            $result = $this->repository->findBySlug('php');

            expect($result)->not->toBeNull()
                ->and($result->slug)->toBe('php')
                ->and($result->id)->toBe($tag->id);
        });

        it('returns null when slug does not exist', function () {
            expect($this->repository->findBySlug('non-existent'))->toBeNull();
        });
    });

    describe('update', function () {
        it('updates a tag', function () {
            $tag = Tag::factory()->create(['name' => 'Old Name']);

            $result = $this->repository->update($tag->id, ['name' => 'New Name']);

            expect($result)->toBeTrue();
            $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New Name']);
        });
    });

    describe('delete', function () {
        it('deletes a tag', function () {
            $tag = Tag::factory()->create();

            $result = $this->repository->delete($tag->id);

            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        });
    });

    describe('all', function () {
        it('gets all tags', function () {
            Tag::factory()->count(5)->create();

            expect($this->repository->all())->toHaveCount(5);
        });

        it('gets all tags with specific columns', function () {
            Tag::factory()->create();

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
            Tag::factory()->create(['name' => 'PHP']);
            Tag::factory()->create(['name' => 'Laravel']);

            $result = $this->repository->query()->where('name', 'PHP')->first();

            expect($result)->not->toBeNull()
                ->and($result->name)->toBe('PHP');
        });
    });
});
