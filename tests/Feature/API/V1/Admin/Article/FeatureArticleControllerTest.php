<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleFeaturedEvent;
use App\Events\Article\ArticleUnfeaturedEvent;
use App\Models\Article;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/FeatureArticleController', function () {
    it('can feature a published article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can unfeature a featured article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => false,
        ]);
    });

    it('can feature a draft article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'is_featured' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can feature a review article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::REVIEW,
            'is_featured' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can feature an archived article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
            'is_featured' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('returns 404 when article does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.article_not_found'));
    });

    it('returns 401 when user is not authenticated', function () {
        $article = Article::factory()->create();

        $response = $this->postJson(route('api.v1.articles.feature', $article));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        $response->assertStatus(403);
    });

    it('toggles featured status correctly', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => false,
        ]);

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response1->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response2->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => false,
        ]);
    });

    it('maintains other article properties when featuring', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'content_html' => '<p>Test content</p>',
            'excerpt' => 'Test excerpt',
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => true,
            'report_count' => 5,
        ];
        $article = Article::factory()->create($originalData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        $article->refresh();
        expect($article->is_featured)->toBeTrue()
            ->and($article->title)->toBe($originalData['title'])
            ->and($article->slug)->toBe($originalData['slug'])
            ->and($article->content_markdown)->toBe($originalData['content_markdown'])
            ->and($article->excerpt)->toBe($originalData['excerpt'])
            ->and($article->status)->toBe($originalData['status'])
            ->and($article->is_pinned)->toBe($originalData['is_pinned'])
            ->and($article->report_count)->toBe($originalData['report_count']);
    });

    it('dispatches ArticleFeaturedEvent when article is featured', function () {
        Event::fake([ArticleFeaturedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(ArticleFeaturedEvent::class, fn ($event) => $event->article->id === $article->id
            && $event->article->is_featured === true);
    });

    it('dispatches ArticleUnfeaturedEvent when article is unfeatured', function () {
        Event::fake([ArticleUnfeaturedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.feature', $article));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(ArticleUnfeaturedEvent::class, fn ($event) => $event->article->id === $article->id
            && $event->article->is_featured === false);
    });
});
