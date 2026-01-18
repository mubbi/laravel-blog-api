<?php

declare(strict_types=1);

use App\Enums\ArticleReactionType;
use App\Enums\UserRole;
use App\Events\Article\ArticleDislikedEvent;
use App\Models\ArticleLike;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/DislikeArticleController', function () {
    it('can dislike an article as authenticated user', function () {
        Event::fake([ArticleDislikedEvent::class]);
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $article = createPublishedArticle($user, $user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('article.disliked_successfully'))
            ->and($response->json('data'))->toBeNull();

        expect(ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->whereNull('ip_address')
            ->exists())->toBeTrue();

        Event::assertDispatched(ArticleDislikedEvent::class, fn ($event) => $event->article->id === $article->id && $event->dislike->type === ArticleReactionType::DISLIKE);
    });

    it('can dislike an article as anonymous user', function () {
        $user = User::factory()->create();
        $article = createPublishedArticle($user, $user);

        $response = $this->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('article.disliked_successfully'));

        expect(ArticleLike::where('article_id', $article->id)
            ->whereNull('user_id')
            ->where('type', ArticleReactionType::DISLIKE->value)
            ->whereNotNull('ip_address')
            ->exists())->toBeTrue();
    });

    it('replaces like with dislike when user previously liked', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $article = createPublishedArticle($user, $user);

        ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::LIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response->getStatusCode())->toBe(200)
            ->and(ArticleLike::where('article_id', $article->id)
                ->where('user_id', $user->id)
                ->where('type', ArticleReactionType::LIKE->value)
                ->exists())->toBeFalse()
            ->and(ArticleLike::where('article_id', $article->id)
                ->where('user_id', $user->id)
                ->where('type', ArticleReactionType::DISLIKE->value)
                ->exists())->toBeTrue();
    });

    it('returns existing dislike if user already disliked the article', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $article = createPublishedArticle($user, $user);

        ArticleLike::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => null,
            'type' => ArticleReactionType::DISLIKE,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response->getStatusCode())->toBe(200)
            ->and(ArticleLike::where('article_id', $article->id)
                ->where('user_id', $user->id)
                ->where('type', ArticleReactionType::DISLIKE->value)
                ->count())->toBe(1);
    });

    it('returns 404 when article not found', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => 'non-existent-slug']));

        $response->assertStatus(404);
    });

    it('returns 404 when article is not published', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $article = createDraftArticle($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.not_found'));
    });

    it('returns 500 when operation fails with exception', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $article = createPublishedArticle($user, $user);

        $this->mock(\App\Services\Interfaces\ArticleServiceInterface::class, function ($mock) {
            $mock->shouldReceive('dislikeArticle')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('api.v1.articles.dislike', ['article' => $article->slug]));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
