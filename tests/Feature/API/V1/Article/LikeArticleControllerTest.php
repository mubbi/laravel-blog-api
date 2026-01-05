<?php

declare(strict_types=1);

use App\Enums\ArticleReactionType;
use App\Models\Article;
use App\Models\ArticleLike;
use App\Models\User;

describe('API/V1/Article/LikeArticleController', function () {
    it('can like an article as authenticated user', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('article.liked_successfully'),
                'data' => null,
            ]);

        // Verify like was created
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::LIKE->value)
            ->whereNull('ip_address')
            ->exists())->toBeTrue();
    });

    it('can like an article as anonymous user', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('article.liked_successfully'),
                'data' => null,
            ]);

        // Verify like was created with IP address
        expect(ArticleLike::where('article_id', $article->id)
            ->whereNull('user_id')
            ->where('type', ArticleReactionType::LIKE->value)
            ->whereNotNull('ip_address')
            ->exists())->toBeTrue();
    });

    it('replaces dislike with like when user previously disliked', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        // Create a dislike first
        ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::DISLIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(200);

        // Verify dislike was removed
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->exists())->toBeFalse();

        // Verify like was created
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::LIKE->value)
            ->exists())->toBeTrue();
    });

    it('returns existing like if user already liked the article', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        // Create a like first
        $existingLike = ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::LIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(200);

        // Verify only one like exists
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::LIKE->value)
            ->count())->toBe(1);
    });

    it('returns 404 when article not found', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => 'non-existent-slug']));

        $response->assertStatus(404);
    });

    it('returns 404 when article is not published', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->draft()
            ->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.not_found'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('returns 500 when operation fails with exception', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        // Mock ArticleService to throw an exception
        $this->mock(\App\Services\ArticleService::class, function ($mock) {
            $mock->shouldReceive('likeArticle')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.like', ['article' => $article->slug]));

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
