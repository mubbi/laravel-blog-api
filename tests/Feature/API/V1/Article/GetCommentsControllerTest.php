<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

describe('API/V1/Article/GetCommentsController', function () {
    it('returns paginated top-level comments with one level of replies', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $topComments = Comment::factory()
            ->count(5)
            ->for($article)
            ->for($user)
            ->create();

        $topComments->each(fn ($parent) => Comment::factory()
            ->count(2)
            ->for($article)
            ->for($user)
            ->reply($parent->id)
            ->create()
        );

        $response = $this->getJson(route('api.v1.articles.comments.index', [
            'article' => $article->slug,
            'per_page' => 3,
            'page' => 1,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'comments' => [
                        '*' => [
                            'id',
                            'user' => ['id', 'name', 'email'],
                            'content',
                            'created_at',
                            'replies_count',
                            'replies' => [
                                '*' => [
                                    'id',
                                    'user' => ['id', 'name', 'email'],
                                    'content',
                                    'created_at',
                                    'replies_count',
                                ],
                            ],
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        // Optional keys if MetaResource includes them
                        // 'last_page', 'from', 'to',
                    ],
                ],
            ]);

        expect($response->json('data.comments'))->toHaveCount(3);
        expect($response->json('data.meta.total'))->toBe(5);
    });

    it('returns empty comment list if article has no comments', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->getJson(route('api.v1.articles.comments.index', [
            'article' => $article->slug,
        ]));

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->where('status', true)
                ->where('message', __('common.success'))
                ->where('data.comments', [])
                ->where('data.meta.current_page', 1)
                ->where('data.meta.per_page', 10)
                ->where('data.meta.total', 0)
                ->etc()
            );
    });

    it('returns 500 on service exception', function () {
        // Create minimal article for route model binding (only needs slug)
        $article = Article::factory()->create([
            'slug' => 'test-article',
            'status' => \App\Enums\ArticleStatus::PUBLISHED->value,
        ]);

        $this->mock(\App\Services\ArticleService::class, function ($mock) {
            $mock->shouldReceive('getArticleComments')
                ->andThrow(new \Exception('Test Exception'));
        });

        $response = $this->getJson(route('api.v1.articles.comments.index', [
            'article' => $article->slug,
        ]));

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
