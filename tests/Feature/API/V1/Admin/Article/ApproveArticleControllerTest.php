<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Events\Article\ArticleApprovedEvent;
use App\Models\Article;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Event;

describe('API/V1/Article/ApproveArticleController', function () {
    it('can approve a draft article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $author = User::factory()->create();
        $article = Article::factory()->for($author, 'author')->create(['status' => ArticleStatus::DRAFT]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', $article));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'slug', 'title', 'status', 'status_display', 'published_at',
            'is_featured', 'is_pinned', 'report_count', 'created_at', 'updated_at',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $auth['user']->id,
        ]);

        expect($article->fresh()->published_at)->not->toBeNull();
    });

    it('can approve a review article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create(['status' => ArticleStatus::REVIEW]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', $article));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $auth['user']->id,
        ]);
    });

    it('can approve an already published article (re-approve)', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $article = Article::factory()->create(['status' => ArticleStatus::PUBLISHED]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', $article));

        expect($response)->toHaveApiSuccessStructure();
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'approved_by' => $auth['user']->id,
        ]);
    });

    it('returns 404 when article does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', 99999));

        $response->assertStatus(404);
    });

    it('returns 401 when user is not authenticated', function () {
        $article = Article::factory()->create();

        $response = $this->postJson(route('api.v1.articles.approve', $article));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', $article));

        $response->assertStatus(403);
    });

    it('updates the approver and published_at timestamp', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

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
        ])->postJson(route('api.v1.articles.approve', $article));

        // Assert
        expect($response)->toHaveApiSuccessStructure();

        $article->refresh();
        $this->assertEquals($admin->id, $article->approved_by);
        $this->assertNotNull($article->published_at);
        $this->assertGreaterThanOrEqual($beforeApproval->timestamp, $article->published_at->timestamp);
    });

    it('dispatches ArticleApprovedEvent when article is approved', function () {
        // Arrange
        Event::fake([ArticleApprovedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $article = Article::factory()->create(['status' => ArticleStatus::DRAFT]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.articles.approve', $article));

        // Assert
        expect($response)->toHaveApiSuccessStructure();

        Event::assertDispatched(ArticleApprovedEvent::class, function ($event) use ($article) {
            return $event->article->id === $article->id
                && $event->article->status === ArticleStatus::PUBLISHED;
        });
    });

    it('creates notification for article author when article is approved', function () {
        // Reset event fake by faking an event that won't be dispatched in this test
        // This resets the global fake and allows all other events to be dispatched
        // With QUEUE_CONNECTION=sync, queued listeners run immediately
        Event::fake([\App\Events\Article\ArticleCreatedEvent::class]);

        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $author = User::factory()->create();
        $article = Article::factory()->for($author, 'author')->create(['status' => ArticleStatus::DRAFT]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.articles.approve', $article));

        expect($response->getStatusCode())->toBe(200);

        // Verify notification was created
        $notification = Notification::where('type', NotificationType::ARTICLE_PUBLISHED->value)
            ->whereJsonContains('message->title', __('notifications.article_published.title'))
            ->first();

        expect($notification)->not->toBeNull();

        // Verify user notification was created for the author
        $userNotification = UserNotification::where('user_id', $author->id)
            ->where('notification_id', $notification->id)
            ->first();

        expect($userNotification)->not->toBeNull()
            ->and($userNotification->is_read)->toBeFalse();
    });
});
