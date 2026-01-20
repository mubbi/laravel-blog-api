<?php

declare(strict_types=1);

use App\Data\Tag\CreateTagDTO;
use App\Data\Tag\UpdateTagDTO;
use App\Enums\CacheKey;
use App\Events\Tag\TagCreatedEvent;
use App\Events\Tag\TagDeletedEvent;
use App\Events\Tag\TagUpdatedEvent;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

describe('TagService', function () {
    beforeEach(function () {
        $this->service = app(TagService::class);
    });

    it('gets all tags from database', function () {
        Tag::factory()->count(5)->create();

        $tags = $this->service->getAllTags();

        expect($tags)->toHaveCount(5)
            ->and($tags->first())->toBeInstanceOf(Tag::class);
    });

    it('returns tags ordered by name', function () {
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);
        Tag::factory()->create(['name' => 'Banana']);

        $tags = $this->service->getAllTags();

        expect($tags->pluck('name')->toArray())->toBe(['Apple', 'Banana', 'Zebra']);
    });

    it('caches tags', function () {
        Cache::forget(CacheKey::TAGS->value);
        Tag::factory()->count(3)->create();

        $tags1 = $this->service->getAllTags();
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        $tags2 = $this->service->getAllTags();
        expect($tags1->count())->toBe($tags2->count());
    });

    it('returns only id, name, and slug fields', function () {
        Tag::factory()->create();

        $tags = $this->service->getAllTags();

        expect($tags->first()->getAttributes())->toHaveKeys(['id', 'name', 'slug']);
    });

    it('returns empty collection when no tags exist', function () {
        $tags = $this->service->getAllTags();

        expect($tags)->toHaveCount(0)
            ->and($tags)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    describe('createTag', function () {
        it('creates a tag and dispatches event', function () {
            Event::fake([TagCreatedEvent::class]);
            $dto = new CreateTagDTO('Test Tag', 'test-tag');

            $tag = $this->service->createTag($dto);

            expect($tag->name)->toBe('Test Tag')
                ->and($tag->slug)->toBe('test-tag');
            Event::assertDispatched(TagCreatedEvent::class, fn ($event) => $event->tag->id === $tag->id);
        });
    });

    describe('updateTag', function () {
        it('updates a tag and dispatches event', function () {
            Event::fake([TagUpdatedEvent::class]);
            $tag = Tag::factory()->create(['name' => 'Old Name']);
            $dto = new UpdateTagDTO('New Name', null);

            $updatedTag = $this->service->updateTag($tag, $dto);

            expect($updatedTag->name)->toBe('New Name');
            Event::assertDispatched(TagUpdatedEvent::class, fn ($event) => $event->tag->id === $tag->id);
        });
    });

    describe('deleteTag', function () {
        it('deletes a tag and dispatches event', function () {
            Event::fake([TagDeletedEvent::class]);
            $tag = Tag::factory()->create();

            $this->service->deleteTag($tag);

            $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
            Event::assertDispatched(TagDeletedEvent::class, fn ($event) => $event->tag->id === $tag->id);
        });
    });
});
