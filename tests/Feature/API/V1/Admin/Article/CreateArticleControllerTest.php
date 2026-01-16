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
        // Arrange
        Event::fake([ArticleCreatedEvent::class]);
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'content_html' => '<h1>Test Content</h1>',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id', 'slug', 'title', 'status', 'status_display', 'published_at',
                    'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'title' => $articleData['title'],
            'status' => ArticleStatus::DRAFT->value,
        ]);

        // Verify event was dispatched
        Event::assertDispatched(ArticleCreatedEvent::class, function ($event) use ($articleData) {
            return $event->article->slug === $articleData['slug'];
        });
    });

    it('can create a published article with published_at in past', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'published_at' => now()->subDay()->toDateTimeString(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'status' => ArticleStatus::PUBLISHED->value,
        ]);
    });

    it('can create a scheduled article with published_at in future', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'published_at' => now()->addDay()->toDateTimeString(),
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('articles', [
            'slug' => $articleData['slug'],
            'status' => ArticleStatus::SCHEDULED->value,
        ]);
    });

    it('can create article with categories and tags', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

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

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull();
        expect($article->categories)->toHaveCount(2);
        expect($article->tags)->toHaveCount(2);
    });

    it('can create article with multiple authors', function () {
        // Arrange
        $admin = User::factory()->create();
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
            'authors' => [
                ['user_id' => $admin->id, 'role' => ArticleAuthorRole::MAIN->value],
                ['user_id' => $author1->id, 'role' => ArticleAuthorRole::CO_AUTHOR->value],
                ['user_id' => $author2->id, 'role' => ArticleAuthorRole::CONTRIBUTOR->value],
            ],
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull();
        expect($article->authors)->toHaveCount(3);
    });

    it('creates article with creator as default author when authors not provided', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

        $article = Article::where('slug', $articleData['slug'])->first();
        expect($article)->not->toBeNull();
        expect($article->authors)->toHaveCount(1);
        expect($article->authors->first()->id)->toBe($admin->id);
    });

    it('can create article with all optional fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

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

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(201);

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
        // Arrange
        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        // Act
        $response = $this->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 422 when validation fails', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        // Act - Missing required fields
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), []);

        // Assert
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
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $articleData = [
            'slug' => 'test-article-'.uniqid(),
            'title' => 'Test Article',
            'content_markdown' => '# Test Content',
        ];

        // Mock service interface to throw exception
        $this->mock(\App\Services\Interfaces\ArticleManagementServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createArticle')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.store'), $articleData);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
