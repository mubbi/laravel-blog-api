<?php

declare(strict_types=1);

use App\Enums\ArticleAuthorRole;
use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleCreatedEvent;
use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/Article/CreateArticleController', function () {
    it('can create a draft article', function () {
        Event::fake([ArticleCreatedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'content_html' => '<h1>Test Content</h1>',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ])->and($response->getStatusCode())->toBe(201);

        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'title' => $articleData['title'],
            'status' => ArticleStatus::DRAFT->value,
        ]);

        Event::assertDispatched(ArticleCreatedEvent::class, fn ($event) => $event->article->slug === $articleData['slug']);
    });

    it('can create a published article with published_at in past', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'published_at' => now()->subDay()->toDateTimeString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'status' => ArticleStatus::PUBLISHED->value,
        ]);
    });

    it('can create a scheduled article with published_at in future', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'published_at' => now()->addDay()->toDateTimeString(),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'status' => ArticleStatus::SCHEDULED->value,
        ]);
    });

    it('can create article with categories and tags', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'category_ids' => [$category1->id, $category2->id],
            'tag_ids' => [$tag1->id, $tag2->id],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response->getStatusCode())->toBe(201);
        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull()
            ->and($article->categories)->toHaveCount(2)
            ->and($article->tags)->toHaveCount(2);
    });

    it('can create article with multiple authors', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'authors' => [
                ['user_id' => $auth['user']->id, 'role' => ArticleAuthorRole::MAIN->value],
                ['user_id' => $author1->id, 'role' => ArticleAuthorRole::CO_AUTHOR->value],
                ['user_id' => $author2->id, 'role' => ArticleAuthorRole::CONTRIBUTOR->value],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);
        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull()
            ->and($article->authors)->toHaveCount(3);
    });

    it('creates article with creator as default author when authors not provided', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);
        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull()
            ->and($article->authors)->toHaveCount(1)
            ->and($article->authors->first()->id)->toBe($auth['user']->id);
    });

    it('can create article with all optional fields', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'subtitle' => 'Test Subtitle',
            'excerpt' => 'Test excerpt',
            'content_markdown' => '# Test Content',
            'content_html' => '<h1>Test Content</h1>',
            'featured_media_id' => null,
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'title' => $articleData['title'],
            'subtitle' => $articleData['subtitle'],
            'excerpt' => $articleData['excerpt'],
            'featured_media_id' => $articleData['featured_media_id'],
            'meta_title' => $articleData['meta_title'],
            'meta_description' => $articleData['meta_description'],
        ]);
    });

    it('returns 401 when user is not authenticated', function () {
        $response = $this->postJson(route('api.v1.articles.store'), [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ]);

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ]);

        $response->assertStatus(403);
    });

    it('returns 422 when validation fails', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), []);

        $response->assertStatus(422);
    });

    it('returns 422 when slug is not unique', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $existingArticle = Article::factory()->create();

        $articleData = [
            'slug' => $existingArticle->slug,
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(422);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        $this->mock(\App\Services\Interfaces\ArticleManagementServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createArticle')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.store'), $articleData);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
