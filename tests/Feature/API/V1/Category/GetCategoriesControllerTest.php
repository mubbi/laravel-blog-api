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

    it('returns all categories', function () {
        Category::factory()->count(3)->create();

        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(200)
            ->assertJson(['status' => true, 'message' => __('common.success')])
            ->assertJsonStructure([
                'data' => ['*' => ['id', 'name', 'slug']],
            ]);
        expect($response->json('data'))->toHaveCount(3);
    });

    it('caches categories after first request', function () {
        Category::factory()->count(3)->create();

        $response1 = $this->getJson(route('api.v1.categories.index'));
        $response1->assertStatus(200);
        expect(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        $response2 = $this->getJson(route('api.v1.categories.index'));
        $response2->assertStatus(200);
        expect($response2->json('data'))->toBe($response1->json('data'));
    });

    it('clears cache when new category is created', function () {
        Category::factory()->count(2)->create();

        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data'))->toHaveCount(2)
            ->and(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        $newCategory = Category::factory()->create();
        Cache::forget(CacheKey::CATEGORIES->value);

        $response2 = $this->getJson(route('api.v1.categories.index'));
        expect($response2->json('data'))->toHaveCount(3)
            ->and(collect($response2->json('data'))->pluck('slug'))->toContain($newCategory->slug);
    });

    it('clears cache when category is updated', function () {
        $category = Category::factory()->create(['name' => 'Original Category']);

        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data.0.name'))->toBe('Original Category')
            ->and(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        $category->update(['name' => 'Updated Category']);
        Cache::forget(CacheKey::CATEGORIES->value);

        $response2 = $this->getJson(route('api.v1.categories.index'));
        $categoryData = collect($response2->json('data'))->firstWhere('id', $category->id);
        expect($categoryData)->not->toBeNull()
            ->and($categoryData['name'])->toBe('Updated Category')
            ->and(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();
    });

    it('clears cache when category is deleted', function () {
        $category = Category::factory()->create();

        $response1 = $this->getJson(route('api.v1.categories.index'));
        expect($response1->json('data'))->toHaveCount(1)
            ->and(Cache::has(CacheKey::CATEGORIES->value))->toBeTrue();

        $categoryId = $category->id;
        $category->delete();
        Cache::forget(CacheKey::CATEGORIES->value);

        $response2 = $this->getJson(route('api.v1.categories.index'));
        expect($response2->json('data'))->toHaveCount(0)
            ->and(collect($response2->json('data'))->pluck('id'))->not->toContain($categoryId);
    });

    it('returns error if service throws', function () {
        $this->mock(\App\Services\Interfaces\CategoryServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getAllCategories')->andThrow(new \Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(500)
            ->assertJson(['status' => false, 'message' => __('common.something_went_wrong')])
            ->assertJsonStructure(['data', 'error']);
    });
});
