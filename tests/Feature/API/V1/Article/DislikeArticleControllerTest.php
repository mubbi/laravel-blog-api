<?php

declare(strict_types=1);

use App\Enums\ArticleReactionType;
use App\Events\Article\ArticleDislikedEvent;
use App\Models\Article;
use App\Models\ArticleLike;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/DislikeArticleController', function () {
    it('can dislike an article as authenticated user', function () {
        Event::fake([ArticleDislikedEvent::class]);
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('article.disliked_successfully'),
                'data' => null,
            ]);

        // Verify dislike was created
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->whereNull('ip_address')
            ->exists())->toBeTrue();

        // Verify event was dispatched
        Event::assertDispatched(ArticleDislikedEvent::class, function ($event) use ($article) {
            return $event->article->id === $article->id && $event->dislike->type === ArticleReactionType::DISLIKE;
        });
    });

    it('can dislike an article as anonymous user', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        $response = $this->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('article.disliked_successfully'),
                'data' => null,
            ]);

        // Verify dislike was created with IP address
        expect(ArticleLike::where('article_id', $article->id)
            ->whereNull('user_id')
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->whereNotNull('ip_address')
            ->exists())->toBeTrue();
    });

    it('replaces like with dislike when user previously liked', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        // Create a like first
        ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::LIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        $response->assertStatus(200);

        // Verify like was removed
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::LIKE->value)
            ->exists())->toBeFalse();

        // Verify dislike was created
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->exists())->toBeTrue();
    });

    it('returns existing dislike if user already disliked the article', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->for($user, 'approver')
            ->published()
            ->create();

        // Create a dislike first
        $existingDislike = ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::DISLIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        $response->assertStatus(200);

        // Verify only one dislike exists
        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->count())->toBe(1);
    });

    it('returns 404 when article not found', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => 'non-existent-slug']));

        $response->assertStatus(404);
    });

    it('returns 404 when article is not published', function () {
        $user = User::factory()->create();
        $article = Article::factory()
            ->for($user, 'author')
            ->draft()
            ->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

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
            $mock->shouldReceive('dislikeArticle')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
