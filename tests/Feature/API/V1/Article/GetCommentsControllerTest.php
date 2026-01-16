<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\User;

describe('API/V1/Article/GetCommentsController', function () {
    it('returns paginated top-level comments with one level of replies', function () {
        $user = User::factory()->create();
        $article = createPublishedArticle($user, $user);

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

        expect($response)->toHaveApiSuccessStructure([
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
            'meta' => ['current_page', 'per_page', 'total'],
        ])->and($response->json('data.comments'))->toHaveCount(3)
            ->and($response->json('data.meta.total'))->toBe(5);
    });

    it('returns empty comment list if article has no comments', function () {
        $user = User::factory()->create();
        $article = createPublishedArticle($user, $user);

        $response = $this->getJson(route('api.v1.articles.comments.index', [
            'article' => $article->slug,
        ]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('status'))->toBeTrue()
            ->and($response->json('message'))->toBe(__('common.success'))
            ->and($response->json('data.comments'))->toBe([])
            ->and($response->json('data.meta.current_page'))->toBe(1)
            ->and($response->json('data.meta.per_page'))->toBe(10)
            ->and($response->json('data.meta.total'))->toBe(0);
    });

    it('returns 500 on service exception', function () {
        $article = createPublishedArticle(null, null, ['slug' => 'test-article']);

        $this->mock(\App\Services\Interfaces\ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticleComments')
                ->andThrow(new \Exception('Test Exception'));
        });

        $response = $this->getJson(route('api.v1.articles.comments.index', [
            'article' => $article->slug,
        ]));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
