<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Article/ShowArticleController', function () {
    it('can show an article with full details', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
            'is_pinned' => false,
            'report_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.articles.show', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'content_markdown', 'content_html', 'excerpt', 'status',
            'is_featured', 'is_pinned', 'report_count', 'published_at', 'created_at', 'updated_at',
            'author' => ['id', 'name', 'email'],
        ])->and($response->json('data.id'))->toBe($article->id)
            ->and($response->json('data.status'))->toBe(ArticleStatus::PUBLISHED->value)
            ->and($response->json('data.is_featured'))->toBeTrue()
            ->and($response->json('data.is_pinned'))->toBeFalse();
    });

    it('can show a draft article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.articles.show', $article));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($article->id)
            ->and($response->json('data.status'))->toBe(ArticleStatus::DRAFT->value);
    });

    it('can show a review article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create(['status' => ArticleStatus::REVIEW]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.articles.show', $article));

        expect($response)->toHaveApiSuccessStructure()
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'status' => ArticleStatus::REVIEW->value,
                ],
            ]);
    });

    it('can show an archived article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::ARCHIVED]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', $article));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'status' => ArticleStatus::ARCHIVED->value,
                ],
            ]);
    });

    it('returns 404 when article does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', 99999));

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
        $response = $this->getJson(route('api.v1.admin.articles.show', $article));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        // Don't attach any roles to test authorization failure

        $token = $user->createToken('test-token', ['access-api']);

        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', $article));

        // Assert
        $response->assertStatus(403);
    });

    it('includes author information in response', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $author = User::factory()->create();
        $article = Article::factory()->for($author, 'author')->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', $article));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'author' => [
                        'id', 'name', 'email',
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'author' => [
                        'id' => $author->id,
                        'name' => $author->name,
                        'email' => $author->email,
                    ],
                ],
            ]);
    });

    it('can show a trashed article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::TRASHED]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', $article));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'status' => ArticleStatus::TRASHED->value,
                ],
            ]);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create();

        // Mock service to throw exception
        $this->mock(\App\Services\Interfaces\ArticleManagementServiceInterface::class, function ($mock) {
            $mock->shouldReceive('loadArticleRelationshipsOnModel')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.articles.show', $article));

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
