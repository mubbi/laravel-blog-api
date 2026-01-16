<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Events\Article\ArticleReportedEvent;
use App\Models\Article;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/ReportArticleController', function () {
    it('can report an article successfully', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'report_count' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.report', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 1,
        ]);
    });

    it('increments report count for multiple reports', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'report_count' => 5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.report', $article));

        expect($response)->toHaveApiSuccessStructure();
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 6,
        ]);
    });

    it('can report a draft article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::DRAFT,
            'report_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 1,
        ]);
    });

    it('can report a review article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::REVIEW,
            'report_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 1,
        ]);
    });

    it('can report an archived article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::ARCHIVED,
            'report_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 1,
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
        ])->postJson(route('api.v1.articles.report', 99999));

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
        $response = $this->postJson(route('api.v1.articles.report', $article));

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
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(403);
    });

    it('maintains other article properties when reporting', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $originalData = [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content_markdown' => 'Test content',
            'content_html' => '<p>Test content</p>',
            'excerpt' => 'Test excerpt',
            'status' => ArticleStatus::PUBLISHED,
            'is_featured' => true,
            'is_pinned' => false,
        ];

        $article = Article::factory()->create($originalData);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(200);

        $article->refresh();
        $this->assertEquals(1, $article->report_count);
        $this->assertEquals($originalData['title'], $article->title);
        $this->assertEquals($originalData['slug'], $article->slug);
        $this->assertEquals($originalData['content_markdown'], $article->content_markdown);
        $this->assertEquals($originalData['excerpt'], $article->excerpt);
        $this->assertEquals($originalData['status'], $article->status);
        $this->assertEquals($originalData['is_featured'], $article->is_featured);
        $this->assertEquals($originalData['is_pinned'], $article->is_pinned);

    });

    it('can report the same article multiple times', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'report_count' => 0,
        ]);

        // Act - First report
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert - Should be 1
        $response1->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 1,
        ]);

        // Act - Second report
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert - Should be 2
        $response2->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 2,
        ]);

        // Act - Third report
        $response3 = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert - Should be 3
        $response3->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'report_count' => 3,
        ]);
    });

    it('dispatches ArticleReportedEvent when article is reported', function () {
        // Arrange
        Event::fake([ArticleReportedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'report_count' => 0,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(ArticleReportedEvent::class, function ($event) use ($article) {
            return $event->article->id === $article->id
                && $event->article->report_count === 1;
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
        ]);

        // Mock service to throw exception
        $this->mock(\App\Services\Interfaces\ArticleReportServiceInterface::class, function ($mock) {
            $mock->shouldReceive('reportArticle')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.report', $article));

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
