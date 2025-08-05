<?php

declare(strict_types=1);

use App\Models\Tag;

describe('API/V1/Tag/GetTagsController', function () {
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

    it('returns error if service throws', function () {
        $this->mock(\App\Services\ArticleService::class, function ($mock) {
            $mock->shouldReceive('getAllTags')
                ->andThrow(new Exception('fail'));
        });

        $response = $this->getJson(route('api.v1.tags.index'));

        $response->assertStatus(500)
            ->assertJson(['status' => false])
            ->assertJson(['message' => __('common.error')])
            ->assertJsonStructure([
                'data',
                'error',
            ]);
    });
});
