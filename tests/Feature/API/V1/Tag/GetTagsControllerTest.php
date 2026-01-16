<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;

describe('API/V1/Tag/GetTagsController', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('returns all tags', function () {
        Tag::factory()->count(4)->create();

        $response = $this->getJson(route('api.v1.tags.index'));

        expect($response)->toHaveApiSuccessStructure([
            '*' => ['id', 'name', 'slug'],
        ])->and($response->json('data'))->toHaveCount(4);
    });

    it('caches tags after first request', function () {
        Tag::factory()->count(4)->create();

        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1)->toHaveApiSuccessStructure();
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2)->toHaveApiSuccessStructure();
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('clears cache when new tag is created', function () {
        Tag::factory()->count(3)->create();

        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data'))->toHaveCount(3)
            ->and(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        $newTag = Tag::factory()->create();
        Cache::forget(CacheKey::TAGS->value);

        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2->json('data'))->toHaveCount(4)
            ->and(collect($response2->json('data'))->pluck('slug'))->toContain($newTag->slug);
    });

    it('clears cache when tag is updated', function () {
        $tag = Tag::factory()->create(['name' => 'Original Tag']);

        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data.0.name'))->toBe('Original Tag')
            ->and(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        $tag->update(['name' => 'Updated Tag']);
        Cache::forget(CacheKey::TAGS->value);

        $response2 = $this->getJson(route('api.v1.tags.index'));
        $tagData = collect($response2->json('data'))->firstWhere('id', $tag->id);
        expect($tagData)->not->toBeNull()
            ->and($tagData['name'])->toBe('Updated Tag')
            ->and(Cache::has(CacheKey::TAGS->value))->toBeTrue();
    });

    it('clears cache when tag is deleted', function () {
        $tag = Tag::factory()->create();

        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data'))->toHaveCount(1)
            ->and(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        $tagId = $tag->id;
        $tag->delete();
        Cache::forget(CacheKey::TAGS->value);

        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2->json('data'))->toHaveCount(0)
            ->and(collect($response2->json('data'))->pluck('id'))->not->toContain($tagId);
    });

    it('returns error if service throws', function () {
        $this->mock(\App\Services\Interfaces\TagServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getAllTags')->andThrow(new \Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.tags.index'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
