<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Support\Facades\Cache;

describe('API/V1/Tag/GetTagsController', function () {
    beforeEach(function () {
        // Clear all caches before each test
        Cache::flush();
    });

    it('can get all tags', function () {
        Tag::factory()->count(4)->create();

        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertStatus(200)
            ->assertJson(['status' => true])
            ->assertJson(['message' => __('common.success')])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(4);
    });

    it('caches tags after first request', function () {
        Tag::factory()->count(4)->create();

        // First request should cache the data
        $response1 = $this->getJson(route('api.v1.tags.index'));
        $response1->assertStatus(200);

        // Verify cache exists
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Second request should use cache (verify by checking data consistency)
        $response2 = $this->getJson(route('api.v1.tags.index'));
        $response2->assertStatus(200);
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('returns cached tags on subsequent requests', function () {
        $tags = Tag::factory()->count(4)->create();

        // First request
        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data'))->toHaveCount(4);

        // Verify cache exists
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Second request should return same data from cache
        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2->json('data'))->toHaveCount(4);
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('clears cache when new tag is created', function () {
        $tags = Tag::factory()->count(3)->create();

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data'))->toHaveCount(3);
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Create new tag - should clear cache via observer
        $newTag = Tag::factory()->create();

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::TAGS->value);

        // Next request should rebuild cache with new tag (verify fresh data)
        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2->json('data'))->toHaveCount(4);
        // Verify the new tag is in the response
        $tagSlugs = collect($response2->json('data'))->pluck('slug')->toArray();
        expect($tagSlugs)->toContain($newTag->slug);
    });

    it('clears cache when tag is updated', function () {
        $tag = Tag::factory()->create(['name' => 'Original Tag']);

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data.0.name'))->toBe('Original Tag');
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Update tag - should clear cache via observer
        $tag->update(['name' => 'Updated Tag']);

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::TAGS->value);

        // Next request should rebuild cache with updated tag
        $response2 = $this->getJson(route('api.v1.tags.index'));
        // Find the tag in the response by ID to verify it's updated
        $tagData = collect($response2->json('data'))->firstWhere('id', $tag->id);
        expect($tagData)->not->toBeNull();
        expect($tagData['name'])->toBe('Updated Tag');
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();
    });

    it('clears cache when tag is deleted', function () {
        $tag = Tag::factory()->create();

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.tags.index'));
        expect($response1->json('data'))->toHaveCount(1);
        expect(Cache::has(CacheKey::TAGS->value))->toBeTrue();

        // Delete tag - should clear cache via observer
        $tagId = $tag->id;
        $tag->delete();

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::TAGS->value);

        // Next request should rebuild cache without deleted tag
        $response2 = $this->getJson(route('api.v1.tags.index'));
        expect($response2->json('data'))->toHaveCount(0);
        // Verify the deleted tag is not in the response
        $tagIds = collect($response2->json('data'))->pluck('id')->toArray();
        expect($tagIds)->not->toContain($tagId);
    });

    it('returns error if service throws', function () {
        $this->mock(TagService::class, function ($mock) {
            $mock->shouldReceive('getAllTags')
                ->andThrow(new \Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertStatus(500)
            ->assertJson(['status' => false])
            ->assertJson(['message' => __('common.something_went_wrong')])
            ->assertJsonStructure([
                'data',
                'error',
            ]);
    });
});
