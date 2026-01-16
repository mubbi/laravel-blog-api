<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Article/GetArticlesController', function () {
    it('can get paginated list of articles', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        Article::factory()->count(5)->create();

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index'));

        expect($response)->toHaveApiSuccessStructure([
            'articles' => [
                '*' => [
                    'id', 'slug', 'title', 'subtitle', 'excerpt', 'content_markdown',
                    'content_html', 'featured_media', 'status', 'status_display',
                    'published_at', 'meta_title', 'meta_description', 'is_featured',
                    'is_pinned', 'featured_at', 'pinned_at', 'report_count',
                    'last_reported_at', 'report_reason', 'created_at', 'updated_at',
                    'author', 'approver', 'updater', 'categories', 'tags',
                    'comments_count', 'authors_count',
                ],
            ],
            'meta' => [
                'current_page', 'from', 'last_page', 'per_page', 'to', 'total',
            ],
        ]);
    });

    it('can filter articles by status', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $publishedArticle = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);
        Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['status' => ArticleStatus::PUBLISHED->value]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($publishedArticle->id);
    });

    it('can filter articles by author', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $author1 = User::factory()->create();
        $author2 = User::factory()->create();
        $article1 = Article::factory()->create(['created_by' => $author1->id]);
        Article::factory()->create(['created_by' => $author2->id]);

        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['author_id' => $author1->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.articles'))->toHaveCount(1)
            ->and($response->json('data.articles.0.id'))->toBe($article1->id);
    });

    it('can filter articles by category', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $article1 = Article::factory()->create();
        $article1->categories()->attach($category1->id);

        $article2 = Article::factory()->create();
        $article2->categories()->attach($category2->id);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['category_id' => $category1->id]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($article1->id, $responseData[0]['id']);
    });

    it('can filter articles by tag', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $tag1 = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        $article1 = Article::factory()->create();
        $article1->tags()->attach($tag1->id);

        $article2 = Article::factory()->create();
        $article2->tags()->attach($tag2->id);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['tag_id' => $tag1->id]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($article1->id, $responseData[0]['id']);
    });

    it('can filter featured articles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $featuredArticle = Article::factory()->create(['is_featured' => true]);
        $regularArticle = Article::factory()->create(['is_featured' => false]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['is_featured' => true]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($featuredArticle->id, $responseData[0]['id']);
    });

    it('can filter pinned articles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $pinnedArticle = Article::factory()->create(['is_pinned' => true]);
        $regularArticle = Article::factory()->create(['is_pinned' => false]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['is_pinned' => true]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($pinnedArticle->id, $responseData[0]['id']);
    });

    it('can filter articles with reports', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $reportedArticle = Article::factory()->create(['report_count' => 3]);
        $cleanArticle = Article::factory()->create(['report_count' => 0]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['has_reports' => true]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($reportedArticle->id, $responseData[0]['id']);
    });

    it('can search articles by title and content', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Create articles with search term in title for full-text search
        // Using "Laravel" which meets MySQL InnoDB minimum token size (3 chars)
        $searchTerm = 'Laravel';
        $article1 = Article::factory()->create([
            'title' => "{$searchTerm} Best Practices Guide",
            'subtitle' => "Learn {$searchTerm} framework development",
            'excerpt' => "This comprehensive article covers {$searchTerm} best practices",
            'content_markdown' => "This comprehensive guide covers {$searchTerm} best practices and coding standards for modern web development.",
            'status' => ArticleStatus::DRAFT->value,
        ]);
        $article2 = Article::factory()->create([
            'title' => 'PHP Programming Tutorial',
            'content_markdown' => 'Learn PHP programming from scratch with examples.',
            'status' => ArticleStatus::DRAFT->value,
        ]);

        // Optimize table to force full-text index update (InnoDB updates indexes asynchronously)
        \Illuminate\Support\Facades\DB::statement('OPTIMIZE TABLE articles');

        // Act - search for "Laravel" which should match article1
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['search' => $searchTerm]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData, 'Should find exactly one article matching "Laravel"');
        $this->assertEquals($article1->id, $responseData[0]['id'], 'Should return the article with Laravel in title');
    });

    it('can sort articles by different fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $article1 = Article::factory()->create(['title' => 'A Article']);
        $article2 = Article::factory()->create(['title' => 'B Article']);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', [
                'sort_by' => 'title',
                'sort_direction' => 'asc',
            ]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(2, $responseData);
        $this->assertEquals($article1->id, $responseData[0]['id']);
        $this->assertEquals($article2->id, $responseData[1]['id']);
    });

    it('can paginate articles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        Article::factory()->count(25)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index', ['per_page' => 10]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(10, $responseData['articles']);
        $this->assertEquals(25, $responseData['meta']['total']);
        $this->assertEquals(3, $responseData['meta']['last_page']);
    });

    it('returns public data when user is not authenticated', function () {
        // Act - unauthenticated users can access, but get only published articles
        $response = $this->getJson(route('api.v1.articles.index'));

        // Assert - should return 200 with public data structure
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'articles',
                    'meta',
                ],
            ]);
    });

    it('returns public data when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        // User has no roles, so no permissions

        // Act - users without permission get public data (only published articles)
        $response = $this->actingAs($user)
            ->getJson(route('api.v1.articles.index'));

        // Assert - should return 200 with public data
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'articles',
                    'meta',
                ],
            ]);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Mock service to throw exception
        $this->mock(\App\Services\Interfaces\ArticleManagementServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getArticles')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.articles.index'));

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
