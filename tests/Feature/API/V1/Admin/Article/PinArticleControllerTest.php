<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticlePinnedEvent;
use App\Models\Article;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/Article/PinArticleController', function () {
    it('can pin a published article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_pinned' => true,
        ]);
    });

    it('can pin a draft article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'is_pinned' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_pinned' => true,
        ]);
    });

    it('returns 404 when article does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', 99999));

        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        $article = Article::factory()->create();

        $response = $this->postJson(route('api.v1.admin.articles.pin', $article));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        $response->assertStatus(403);
    });

    it('maintains other article properties when pinning', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
            'report_count' => 5,
        ];
        $article = Article::factory()->create($originalData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        expect($response->getStatusCode())->toBe(200);
        $article->refresh();
        expect($article->is_pinned)->toBeTrue()
            ->and($article->title)->toBe($originalData['title'])
            ->and($article->slug)->toBe($originalData['slug'])
            ->and($article->status)->toBe($originalData['status'])
            ->and($article->is_featured)->toBe($originalData['is_featured'])
            ->and($article->report_count)->toBe($originalData['report_count']);
    });

    it('dispatches ArticlePinnedEvent when article is pinned', function () {
        Event::fake([ArticlePinnedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(ArticlePinnedEvent::class, fn ($event) => $event->article->id === $article->id
            && $event->article->is_pinned === true);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->mock(\App\Services\Interfaces\ArticleFeatureServiceInterface::class, function ($mock) {
            $mock->shouldReceive('pinArticle')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.admin.articles.pin', $article));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
