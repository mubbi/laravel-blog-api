<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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
});
