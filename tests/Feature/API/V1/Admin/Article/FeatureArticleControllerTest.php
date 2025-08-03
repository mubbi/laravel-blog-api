<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Article/FeatureArticleController', function () {
    it('can feature a published article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => false,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id', 'slug', 'title', 'status', 'status_display', 'published_at',
                    'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can unfeature a featured article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => false,
        ]);
    });

    it('can feature a draft article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'is_featured' => false,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can feature a review article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::REVIEW,
            'is_featured' => false,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('can feature an archived article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
            'is_featured' => false,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);
    });

    it('returns 404 when article does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.article_not_found'),
            ]);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $article = Article::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($subscriberRole->id);

        $token = $user->createToken('test-token', ['access-api']);

        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(403);
    });

    it('toggles featured status correctly', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => false,
        ]);

        // Act - First call to feature
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert - Should be featured now
        $response1->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => true,
        ]);

        // Act - Second call to unfeature
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert - Should be unfeatured now
        $response2->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_featured' => false,
        ]);
    });

    it('maintains other article properties when featuring', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

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

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.feature', $article->id));

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertTrue($article->is_featured);
        $this->assertEquals($originalData['title'], $article->title);
        $this->assertEquals($originalData['slug'], $article->slug);
        $this->assertEquals($originalData['content_markdown'], $article->content_markdown);
        $this->assertEquals($originalData['excerpt'], $article->excerpt);
        $this->assertEquals($originalData['status'], $article->status);
        $this->assertEquals($originalData['is_pinned'], $article->is_pinned);
        $this->assertEquals($originalData['report_count'], $article->report_count);

    });
});
