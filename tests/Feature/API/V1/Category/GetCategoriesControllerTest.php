<?php

declare(strict_types=1);

use App\Models\Category;

describe('API/V1/Category/GetCategoriesController', function () {
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

    it('returns error if service throws', function () {
        $this->mock(\App\Services\ArticleService::class, function ($mock) {
            $mock->shouldReceive('getAllCategories')
                ->andThrow(new Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.categories.index'));

        $response->assertStatus(500)
            ->assertJson(['status' => false])
            ->assertJson(['message' => __('common.error')])
            ->assertJsonStructure([
                'data',
                'error',
            ]);
    });
});
