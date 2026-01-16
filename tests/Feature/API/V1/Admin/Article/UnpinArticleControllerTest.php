<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleUnpinnedEvent;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/UnpinArticleController', function () {
    it('can unpin a pinned article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.unpin', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_pinned' => false,
        ]);
    });

    it('can unpin an already unpinned article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => false,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.unpin', $article));

        // Assert
        expect($response)->toHaveApiSuccessStructure();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_pinned' => false,
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
        ])->postJson(route('api.v1.articles.unpin', 99999));

        // Assert
        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $article = Article::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.articles.unpin', $article));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.unpin', $article));

        // Assert
        $response->assertStatus(403);
    });

    it('maintains other article properties when unpinning', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
            'is_pinned' => true,
            'report_count' => 5,
        ];

        $article = Article::factory()->create($originalData);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.unpin', $article));

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertFalse($article->is_pinned);
        $this->assertEquals($originalData['title'], $article->title);
        $this->assertEquals($originalData['slug'], $article->slug);
        $this->assertEquals($originalData['status'], $article->status);
        $this->assertEquals($originalData['is_featured'], $article->is_featured);
        $this->assertEquals($originalData['report_count'], $article->report_count);
    });

    it('dispatches ArticleUnpinnedEvent when article is unpinned', function () {
        // Arrange
        Event::fake([ArticleUnpinnedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => true,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.unpin', $article));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(ArticleUnpinnedEvent::class, function ($event) use ($article) {
            return $event->article->id === $article->id
                && $event->article->is_pinned === false;
        });
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'is_pinned' => true,
        ]);

        // Mock service to throw exception
        $this->mock(\App\Services\Interfaces\ArticleFeatureServiceInterface::class, function ($mock) {
            $mock->shouldReceive('unpinArticle')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.unpin', $article));

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
