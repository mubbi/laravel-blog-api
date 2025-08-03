<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Article/ApproveArticleController', function () {
    it('can approve a draft article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        // Act
        $admin->withAccessToken($token->accessToken);
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.articles.approve', $article->id));

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
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $admin->id,
        ]);

        $this->assertNotNull($article->fresh()->published_at);
    });

    it('can approve a review article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::REVIEW]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.approve', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $admin->id,
        ]);
    });

    it('can approve an already published article (re-approve)', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.approve', $article->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $admin->id,
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
        ])->postJson(route('api.v1.admin.articles.approve', 99999));

        // Assert
        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $article = Article::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.admin.articles.approve', $article->id));

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
        ])->postJson(route('api.v1.admin.articles.approve', $article->id));

        // Assert
        $response->assertStatus(403);
    });

    it('updates the approver and published_at timestamp', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'approved_by' => null,
            'published_at' => null,
        ]);

        $beforeApproval = now();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.admin.articles.approve', $article->id));

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertEquals($admin->id, $article->approved_by);
        $this->assertNotNull($article->published_at);
        $this->assertGreaterThanOrEqual($beforeApproval->timestamp, $article->published_at->timestamp);
    });
});
