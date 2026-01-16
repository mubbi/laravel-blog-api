<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleRestoredEvent;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/Article/RestoreArticleController', function () {
    it('can restore an archived article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.restore', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
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
        ])->postJson(route('api.v1.articles.restore', 99999));

        // Assert
        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.articles.restore', $article));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.restore', $article));

        // Assert
        $response->assertStatus(403);
    });

    it('maintains other article properties when restoring', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'status' => ArticleStatus::ARCHIVED,
            'is_featured' => true,
            'is_pinned' => true,
            'report_count' => 5,
        ];

        $article = Article::factory()->create($originalData);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.restore', $article));

        // Assert
        expect($response)->toHaveApiSuccessStructure();

        $article->refresh();
        $this->assertEquals(ArticleStatus::PUBLISHED, $article->status);
        $this->assertEquals($originalData['title'], $article->title);
        $this->assertEquals($originalData['slug'], $article->slug);
        $this->assertEquals($originalData['is_featured'], $article->is_featured);
        $this->assertEquals($originalData['is_pinned'], $article->is_pinned);
        $this->assertEquals($originalData['report_count'], $article->report_count);
    });

    it('dispatches ArticleRestoredEvent when article is restored', function () {
        // Arrange
        Event::fake([ArticleRestoredEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.restore', $article));

        // Assert
        expect($response)->toHaveApiSuccessStructure();

        // Refresh article to ensure we have the latest status
        $article->refresh();

        Event::assertDispatched(ArticleRestoredEvent::class, function ($event) use ($article) {
            return $event->article->id === $article->id
                && $event->article->status === ArticleStatus::PUBLISHED;
        });
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        // Mock service to throw exception
        $this->mock(\App\Services\Interfaces\ArticleStatusServiceInterface::class, function ($mock) {
            $mock->shouldReceive('restoreArticle')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.restore', $article));

        // Assert
        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
