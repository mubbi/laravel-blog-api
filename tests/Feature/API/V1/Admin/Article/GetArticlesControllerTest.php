<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Category;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;

describe('API/V1/Admin/Article/GetArticlesController', function () {
    it('can get paginated list of articles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $articles = Article::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.articles.index'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'articles' => [
                        '*' => [
                            'id', 'slug', 'title', 'subtitle', 'excerpt', 'content_markdown',
                            'content_html', 'featured_image', 'status', 'status_display',
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
                ],
            ]);
    });

    it('can filter articles by status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $publishedArticle = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);
        $draftArticle = Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.articles.index', ['status' => ArticleStatus::PUBLISHED->value]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($publishedArticle->id, $responseData[0]['id']);
    });

    it('can filter articles by author', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $author1 = User::factory()->create();
        $author2 = User::factory()->create();

        $article1 = Article::factory()->create(['created_by' => $author1->id]);
        $article2 = Article::factory()->create(['created_by' => $author2->id]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.articles.index', ['author_id' => $author1->id]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($article1->id, $responseData[0]['id']);
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
            ->getJson(route('api.v1.admin.articles.index', ['category_id' => $category1->id]));

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
            ->getJson(route('api.v1.admin.articles.index', ['tag_id' => $tag1->id]));

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
            ->getJson(route('api.v1.admin.articles.index', ['is_featured' => true]));

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
            ->getJson(route('api.v1.admin.articles.index', ['is_pinned' => true]));

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
            ->getJson(route('api.v1.admin.articles.index', ['has_reports' => true]));

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

        $article1 = Article::factory()->create(['title' => 'PHP Best Practices']);
        $article2 = Article::factory()->create(['title' => 'Laravel Tutorial']);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.articles.index', ['search' => 'PHP']));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data.articles');
        $this->assertCount(1, $responseData);
        $this->assertEquals($article1->id, $responseData[0]['id']);
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
            ->getJson(route('api.v1.admin.articles.index', [
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
            ->getJson(route('api.v1.admin.articles.index', ['per_page' => 10]));

        // Assert
        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(10, $responseData['articles']);
        $this->assertEquals(25, $responseData['meta']['total']);
        $this->assertEquals(3, $responseData['meta']['last_page']);
    });

    it('returns 401 when user is not authenticated', function () {
        // Act
        $response = $this->getJson(route('api.v1.admin.articles.index'));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        // User has no roles, so no permissions

        // Act
        $response = $this->actingAs($user)
            ->getJson(route('api.v1.admin.articles.index'));

        // Assert
        $response->assertStatus(403);
    });
});
