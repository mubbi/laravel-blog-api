<?php

declare(strict_types=1);

use App\Data\FilterMediaDTO;
use App\Data\UpdateMediaMetadataDTO;
use App\Data\UploadMediaDTO;
use App\Events\Media\MediaDeletedEvent;
use App\Events\Media\MediaMetadataUpdatedEvent;
use App\Events\Media\MediaUploadedEvent;
use App\Models\Media;
use App\Models\User;
use App\Services\Interfaces\MediaServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

describe('MediaService', function () {
    beforeEach(function () {
        $this->service = app(MediaServiceInterface::class);
        Storage::fake('public');
    });

    describe('uploadMedia', function () {
        it('uploads media file successfully and dispatches event', function () {
            Event::fake([MediaUploadedEvent::class]);
            $user = User::factory()->create();
            $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);
            $dto = new UploadMediaDTO(
                name: 'Test Image',
                altText: 'Test alt text',
                caption: 'Test caption',
                description: 'Test description',
                disk: 'public',
                uploadedBy: $user->id
            );

            $media = $this->service->uploadMedia($file, $dto);

            expect($media)->toBeInstanceOf(Media::class)
                ->and($media->name)->toBe('Test Image')
                ->and($media->alt_text)->toBe('Test alt text')
                ->and($media->type)->toBe('image')
                ->and($media->uploaded_by)->toBe($user->id);

            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'name' => 'Test Image',
                'uploaded_by' => $user->id,
            ]);

            Storage::disk('public')->assertExists($media->path);
            Event::assertDispatched(MediaUploadedEvent::class, fn ($event) => $event->media->id === $media->id);
        });

        it('uses original file name when name is not provided', function () {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->image('original-image.jpg');
            $dto = new UploadMediaDTO(null, null, null, null, 'public', $user->id);

            $media = $this->service->uploadMedia($file, $dto);

            expect($media->name)->toBe('original-image.jpg');
        });

        it('extracts metadata for images', function () {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->image('test.jpg', 800, 600);
            $dto = new UploadMediaDTO(null, null, null, null, 'public', $user->id);

            $media = $this->service->uploadMedia($file, $dto);

            expect($media->metadata)
                ->toBeArray()
                ->toHaveKeys(['width', 'height', 'dimensions']);
        });

        it('throws exception when file size exceeds limit', function () {
            $user = User::factory()->create();
            $file = UploadedFile::fake()->create('large-file.pdf', 11000); // 11MB
            $dto = new UploadMediaDTO(null, null, null, null, 'public', $user->id);

            expect(fn () => $this->service->uploadMedia($file, $dto))
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('updateMediaMetadata', function () {
        it('updates media metadata successfully and dispatches event', function () {
            Event::fake([MediaMetadataUpdatedEvent::class]);
            $media = Media::factory()->create(['name' => 'Old Name', 'alt_text' => 'Old alt text']);
            $dto = new UpdateMediaMetadataDTO('New Name', 'New alt text', 'New caption', 'New description');

            $updatedMedia = $this->service->updateMediaMetadata($media->id, $dto);

            expect($updatedMedia->name)->toBe('New Name')
                ->and($updatedMedia->alt_text)->toBe('New alt text')
                ->and($updatedMedia->caption)->toBe('New caption')
                ->and($updatedMedia->description)->toBe('New description');

            $this->assertDatabaseHas('media', [
                'id' => $media->id,
                'name' => 'New Name',
                'alt_text' => 'New alt text',
            ]);

            Event::assertDispatched(MediaMetadataUpdatedEvent::class, fn ($event) => $event->media->id === $media->id);
        });

        it('only updates provided fields', function () {
            $media = Media::factory()->create([
                'name' => 'Original Name',
                'alt_text' => 'Original alt text',
                'caption' => 'Original caption',
            ]);
            $dto = new UpdateMediaMetadataDTO('Updated Name', null, null, null);

            $updatedMedia = $this->service->updateMediaMetadata($media->id, $dto);

            expect($updatedMedia->name)->toBe('Updated Name')
                ->and($updatedMedia->alt_text)->toBe('Original alt text')
                ->and($updatedMedia->caption)->toBe('Original caption');
        });

        it('does nothing when DTO has no updates', function () {
            $media = Media::factory()->create(['name' => 'Original Name']);
            $dto = new UpdateMediaMetadataDTO(null, null, null, null);

            $updatedMedia = $this->service->updateMediaMetadata($media->id, $dto);

            expect($updatedMedia->name)->toBe('Original Name');
        });
    });

    describe('deleteMedia', function () {
        it('deletes media file and record successfully and dispatches event', function () {
            Event::fake([MediaDeletedEvent::class]);
            $media = Media::factory()->create();
            $path = $media->path;
            Storage::disk('public')->put($path, 'fake content');

            $deleted = $this->service->deleteMedia($media->id);

            expect($deleted)->toBeTrue();
            $this->assertDatabaseMissing('media', ['id' => $media->id]);
            Storage::disk('public')->assertMissing($path);
            Event::assertDispatched(MediaDeletedEvent::class, fn ($event) => $event->media->id === $media->id);
        });

        it('deletes record even if file does not exist', function () {
            $media = Media::factory()->create();

            $deleted = $this->service->deleteMedia($media->id);

            expect($deleted)->toBeTrue();
            $this->assertDatabaseMissing('media', ['id' => $media->id]);
        });

        it('throws exception when media does not exist', function () {
            expect(fn () => $this->service->deleteMedia(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getMediaById', function () {
        it('returns media with uploader relationship', function () {
            $user = User::factory()->create();
            $media = Media::factory()->for($user, 'uploader')->create();

            $result = $this->service->getMediaById($media->id);

            expect($result)->toBeInstanceOf(Media::class)
                ->and($result->id)->toBe($media->id)
                ->and($result->relationLoaded('uploader'))->toBeTrue()
                ->and($result->uploader->id)->toBe($user->id);
        });

        it('throws exception when media does not exist', function () {
            expect(fn () => $this->service->getMediaById(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getMediaLibrary', function () {
        it('returns paginated media library', function () {
            Media::factory()->count(20)->create();
            $dto = new FilterMediaDTO(1, 10, 'created_at', 'desc', null, null, null);

            $result = $this->service->getMediaLibrary($dto);

            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
                ->and($result->count())->toBe(10)
                ->and($result->total())->toBe(20);
        });

        it('filters by type', function () {
            Media::factory()->image()->count(5)->create();
            Media::factory()->video()->count(3)->create();
            $dto = new FilterMediaDTO(1, 15, 'created_at', 'desc', 'image', null, null);

            $result = $this->service->getMediaLibrary($dto);

            expect($result->count())->toBe(5)
                ->and($result->first()->type)->toBe('image');
        });

        it('filters by uploaded_by', function () {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            Media::factory()->for($user1, 'uploader')->count(3)->create();
            Media::factory()->for($user2, 'uploader')->count(2)->create();
            $dto = new FilterMediaDTO(1, 15, 'created_at', 'desc', null, null, $user1->id);

            $result = $this->service->getMediaLibrary($dto);

            expect($result->count())->toBe(3)
                ->and($result->first()->uploaded_by)->toBe($user1->id);
        });

        it('searches by name, file_name, or alt_text', function () {
            Media::factory()->create(['name' => 'Test Image']);
            Media::factory()->create(['file_name' => 'test-file.jpg']);
            Media::factory()->create(['alt_text' => 'Test alt text']);
            Media::factory()->create(['name' => 'Other']);
            $dto = new FilterMediaDTO(1, 15, 'created_at', 'desc', null, 'Test', null);

            $result = $this->service->getMediaLibrary($dto);

            expect($result->count())->toBe(3);
        });

        it('sorts by specified field', function () {
            Media::factory()->create(['name' => 'A']);
            Media::factory()->create(['name' => 'Z']);
            Media::factory()->create(['name' => 'M']);
            $dto = new FilterMediaDTO(1, 15, 'name', 'asc', null, null, null);

            $result = $this->service->getMediaLibrary($dto);

            expect($result->first()->name)->toBe('A')
                ->and($result->last()->name)->toBe('Z');
        });
    });
});
