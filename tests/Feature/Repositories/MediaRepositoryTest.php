<?php

declare(strict_types=1);

use App\Models\Media;
use App\Models\User;
use App\Repositories\Contracts\MediaRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MediaRepository', function () {
    beforeEach(function () {
        $this->repository = app(MediaRepositoryInterface::class);
    });

    describe('create', function () {
        it('creates a media record', function () {
            $user = User::factory()->create();
            $data = [
                'name' => 'Test Media',
                'file_name' => 'test.jpg',
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'path' => 'media/test.jpg',
                'url' => '/storage/media/test.jpg',
                'size' => 1024,
                'type' => 'image',
                'uploaded_by' => $user->id,
            ];

            $result = $this->repository->create($data);

            expect($result)->toBeInstanceOf(Media::class)
                ->and($result->name)->toBe('Test Media');
            $this->assertDatabaseHas('media', [
                'name' => 'Test Media',
                'uploaded_by' => $user->id,
            ]);
        });
    });

    describe('findById', function () {
        it('finds media by id', function () {
            $media = Media::factory()->create();

            $result = $this->repository->findById($media->id);

            expect($result)->not->toBeNull()
                ->and($result->id)->toBe($media->id);
        });

        it('returns null when media does not exist', function () {
            expect($this->repository->findById(99999))->toBeNull();
        });
    });

    describe('findOrFail', function () {
        it('finds media by id or fails', function () {
            $media = Media::factory()->create();

            $result = $this->repository->findOrFail($media->id);

            expect($result)->toBeInstanceOf(Media::class)
                ->and($result->id)->toBe($media->id);
        });

        it('throws exception when media does not exist', function () {
            expect(fn () => $this->repository->findOrFail(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('update', function () {
        it('updates media record', function () {
            $media = Media::factory()->create(['name' => 'Old Name']);
            $data = ['name' => 'New Name', 'alt_text' => 'New alt text'];

            $result = $this->repository->update($media->id, $data);

            expect($result)->toBeTrue();
            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'name' => 'New Name',
                'alt_text' => 'New alt text',
            ]);
        });

        it('returns false when no data to update', function () {
            $media = Media::factory()->create();

            expect($this->repository->update($media->id, []))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('deletes media record', function () {
            $media = Media::factory()->create();

            $result = $this->repository->delete($media->id);

            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('media', ['id' => $media->id]);
        });

        it('throws exception when media does not exist', function () {
            expect(fn () => $this->repository->delete(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('query', function () {
        it('returns query builder instance', function () {
            expect($this->repository->query())
                ->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('chains query methods', function () {
            Media::factory()->image()->count(3)->create();
            Media::factory()->video()->count(2)->create();

            $result = $this->repository->query()
                ->where('type', 'image')
                ->get();

            expect($result)->toHaveCount(3);
        });
    });

    describe('paginate', function () {
        it('paginates media records', function () {
            Media::factory()->count(25)->create();

            $result = $this->repository->paginate(['page' => 1, 'per_page' => 10]);

            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
                ->and($result->count())->toBe(10)
                ->and($result->total())->toBe(25);
        });
    });

    describe('all', function () {
        it('gets all media records', function () {
            Media::factory()->count(5)->create();

            $result = $this->repository->all();

            expect($result)->toHaveCount(5)
                ->and($result->first())->toBeInstanceOf(Media::class);
        });

        it('returns empty collection when no records exist', function () {
            expect($this->repository->all())->toHaveCount(0);
        });
    });
});
