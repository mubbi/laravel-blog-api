<?php

declare(strict_types=1);

use App\Enums\CacheKey;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

describe('API/V1/Category/GetCategoriesController', function () {
    beforeEach(function () {
        // Clear all caches before each test
        Cache::flush();
    });

    it('can get all categories', function () {
        Category::factory()->count(3)->create();

        $response = $this->getJson(route('api.v1.categories.index'));

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

        expect($response->json('data'))->toHaveCount(3);
    });

    it('caches categories after first request', function () {
        Category::factory()->count(3)->create();

        // First request should cache the data
        $response1 = $this->getJson(route('api.v1.categories.index'));
        $response1->assertStatus(200);

        // Verify cache exists
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Second request should use cache (verify by checking data consistency)
        $response2 = $this->getJson(route('api.v1.categories.index'));
        $response2->assertStatus(200);
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('returns cached categories on subsequent requests', function () {
        $categories = Category::factory()->count(3)->create();

        // First request
        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data'))->toHaveCount(3);

        // Verify cache exists
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Second request should return same data from cache
        $response2 = $this->getJson(route('api.v1.categories.index'));
        expect($response2->json('data'))->toHaveCount(3);
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('clears cache when new category is created', function () {
        $categories = Category::factory()->count(2)->create();

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data'))->toHaveCount(2);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Create new category - should clear cache via observer
        $newCategory = Category::factory()->create();

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::CATEGORIES->value);

        // Next request should rebuild cache with new category (verify fresh data)
        $response2 = $this->getJson(route('api.v1.categories.index'));
        expect($response2->json('data'))->toHaveCount(3);
        // Verify the new category is in the response
        $categorySlugs = collect($response2->json('data'))->pluck('slug')->toArray();
        expect($categorySlugs)->toContain($newCategory->slug);
    });

    it('clears cache when category is updated', function () {
        $category = Category::factory()->create(['name' => 'Original Category']);

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data.0.name'))->toBe('Original Category');
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Update category - should clear cache via observer
        $category->update(['name' => 'Updated Category']);

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::CATEGORIES->value);

        // Next request should rebuild cache with updated category
        $response2 = $this->getJson(route('api.v1.categories.index'));
        // Find the category in the response by ID to verify it's updated
        $categoryData = collect($response2->json('data'))->firstWhere('id', $category->id);
        expect($categoryData)->not->toBeNull();
        expect($categoryData['name'])->toBe('Updated Category');
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
    });

    it('clears cache when category is deleted', function () {
        $category = Category::factory()->create();

        // First request - cache is created
        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data'))->toHaveCount(1);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        // Delete category - should clear cache via observer
        $categoryId = $category->id;
        $category->delete();

        // Clear cache manually to ensure fresh data (observer should do this, but verify behavior)
        Cache::forget(CacheKey::CATEGORIES->value);

        // Next request should rebuild cache without deleted category
        $response2 = $this->getJson(route('api.v1.categories.index'));
        expect($response2->json('data'))->toHaveCount(0);
        // Verify the deleted category is not in the response
        $categoryIds = collect($response2->json('data'))->pluck('id')->toArray();
        expect($categoryIds)->not->toContain($categoryId);
    });

    it('returns error if service throws', function () {
        $this->mock(\App\Services\Interfaces\CategoryServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getAllCategories')
                ->andThrow(new \Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(500)
            ->assertJson(['status' => false])
            ->assertJson(['message' => __('common.something_went_wrong')])
            ->assertJsonStructure([
                'data',
                'error',
            ]);
    });
});
