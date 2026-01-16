<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleArchivedEvent;
use App\Models\Article;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/ArchiveArticleController', function () {
    it('can archive a published article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::ARCHIVED->value,
        ]);
    });

    it('can archive a review article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::REVIEW,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::ARCHIVED->value,
        ]);
    });

    it('can archive a draft article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::ARCHIVED->value,
        ]);
    });

    it('returns 404 when article does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', 99999));

        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        $article = Article::factory()->create();

        $response = $this->postJson(route('api.v1.articles.archive', $article));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        $response->assertStatus(403);
    });

    it('maintains other article properties when archiving', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'content_html' => '<p>Test content</p>',
            'excerpt' => 'Test excerpt',
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
            'is_pinned' => true,
            'report_count' => 5,
        ];
        $article = Article::factory()->create($originalData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response->getStatusCode())->toBe(200);
        $article->refresh();
        expect($article->status)->toBe(ArticleStatus::ARCHIVED)
            ->and($article->title)->toBe($originalData['title'])
            ->and($article->slug)->toBe($originalData['slug'])
            ->and($article->content_markdown)->toBe($originalData['content_markdown'])
            ->and($article->excerpt)->toBe($originalData['excerpt'])
            ->and($article->is_featured)->toBe($originalData['is_featured'])
            ->and($article->is_pinned)->toBe($originalData['is_pinned'])
            ->and($article->report_count)->toBe($originalData['report_count']);
    });

    it('dispatches ArticleArchivedEvent when article is archived', function () {
        Event::fake([ArticleArchivedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response->getStatusCode())->toBe(200);
        $article->refresh();
        Event::assertDispatched(ArticleArchivedEvent::class, fn ($event) => $event->article->id === $article->id
            && $event->article->status === ArticleStatus::ARCHIVED);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->mock(\App\Services\Interfaces\ArticleStatusServiceInterface::class, function ($mock) {
            $mock->shouldReceive('archiveArticle')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.archive', $article));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
