<?php

declare(strict_types=1);

use App\Data\CreateTagDTO;
use App\Data\UpdateTagDTO;
use App\Enums\CacheKey;
use App\Events\Tag\TagCreatedEvent;
use App\Events\Tag\TagDeletedEvent;
use App\Events\Tag\TagUpdatedEvent;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('TagService', function () {
    beforeEach(function () {
        $this->service = app(TagService::class);
    });

    it('can get all tags from database', function () {
        // Arrange
        Tag::factory()->count(5)->create();

        // Act
        $tags = $this->service->getAllTags();

        // Assert
        expect($tags)->toHaveCount(5);
        expect($tags->first())->toBeInstanceOf(Tag::class);
    });

    it('returns tags ordered by name', function () {
        // Arrange
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);
        Tag::factory()->create(['name' => 'Banana']);

        // Act
        $tags = $this->service->getAllTags();

        // Assert
        expect($tags->pluck('name')->toArray())->toBe(['Apple', 'Banana', 'Zebra']);
    });

    it('caches tags', function () {
        // Arrange
        Cache::forget(CacheKey::TAGS->value);
        Tag::factory()->count(3)->create();

        // Act - First call
        $tags1 = $this->service->getAllTags();

        // Assert - Should be cached
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Act - Second call should use cache
        $tags2 = $this->service->getAllTags();

        // Assert
        expect($tags1->count())->toBe($tags2->count());
    });

    it('returns only id, name, and slug fields', function () {
        // Arrange
        $tag = Tag::factory()->create();

        // Act
        $tags = $this->service->getAllTags();

        // Assert
        $firstTag = $tags->first();
        expect($firstTag->getAttributes())->toHaveKeys(['id', 'name', 'slug']);
    });

    it('returns empty collection when no tags exist', function () {
        // Act
        $tags = $this->service->getAllTags();

        // Assert
        expect($tags)->toHaveCount(0);
        expect($tags)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    describe('createTag', function () {
        it('creates a tag successfully and dispatches event', function () {
            // Arrange
            Event::fake();
            $dto = new CreateTagDTO(
                name: 'Test Tag',
                slug: 'test-tag'
            );

            // Act
            $tag = $this->service->createTag($dto);

            // Assert
            expect($tag->name)->toBe('Test Tag');
            expect($tag->slug)->toBe('test-tag');
            Event::assertDispatched(TagCreatedEvent::class, function ($event) use ($tag) {
                return $event->tag->id === $tag->id;
            });
        });
    });

    describe('updateTag', function () {
        it('updates a tag successfully and dispatches event', function () {
            // Arrange
            Event::fake();
            $tag = Tag::factory()->create(['name' => 'Old Name']);
            $dto = new UpdateTagDTO(
                name: 'New Name',
                slug: null
            );

            // Act
            $updatedTag = $this->service->updateTag($tag, $dto);

            // Assert
            expect($updatedTag->name)->toBe('New Name');
            Event::assertDispatched(TagUpdatedEvent::class, function ($event) use ($tag) {
                return $event->tag->id === $tag->id;
            });
        });
    });

    describe('deleteTag', function () {
        it('deletes a tag successfully and dispatches event', function () {
            // Arrange
            Event::fake();
            $tag = Tag::factory()->create();

            // Act
            $this->service->deleteTag($tag);

            // Assert
            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
            Event::assertDispatched(TagDeletedEvent::class, function ($event) use ($tag) {
                return $event->tag->id === $tag->id;
            });
        });
    });
});
