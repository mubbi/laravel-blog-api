<?php

declare(strict_types=1);

use App\Data\Comment\ApproveCommentDTO;
use App\Enums\CommentStatus;
use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Events\Comment\CommentApprovedEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('API/V1/Comment/ApproveCommentController', function () {
    it('can approve a pending comment successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved after review',
            ]);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'content',
            'status',
            'status_display',
            'is_approved',
            'approved_by',
            'approved_at',
            'report_count',
            'created_at',
            'updated_at',
        ])->and($response->json('data.id'))->toBe($comment->id)
            ->and($response->json('data.status'))->toBe(CommentStatus::APPROVED->value);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::APPROVED->value,
            'admin_note' => 'Approved after review',
        ]);
    });

    it('can approve a comment without admin note', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.id'))->toBe($comment->id)
            ->and($response->json('data.status'))->toBe(CommentStatus::APPROVED->value);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::APPROVED->value,
        ]);
    });

    it('can approve an already approved comment', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Re-approved',
            ]);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($comment->id)
            ->and($response->json('data.status'))->toBe(CommentStatus::APPROVED->value);
    });

    it('can approve a rejected comment', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::REJECTED->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved after reconsideration',
            ]);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($comment->id)
            ->and($response->json('data.status'))->toBe(CommentStatus::APPROVED->value);
    });

    it('returns 404 when comment does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $nonExistentId = 99999;

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $nonExistentId), [
                'admin_note' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.comment_not_found'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('returns 403 when user lacks approve_comments permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->postJson(route('api.v1.comments.approve', $comment), [
            'admin_note' => 'Test note',
        ]);

        // Assert
        $response->assertStatus(401);
    });

    it('validates admin_note field', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => str_repeat('a', 501), // Exceeds max length
            ]);

        // Assert - admin_note should be validated and return 422 for exceeding max length
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The admin note field must not be greater than 500 characters.',
                'data' => null,
                'error' => [
                    'admin_note' => ['The admin note field must not be greater than 500 characters.'],
                ],
            ]);
    });

    it('handles service exception and logs error', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('approveComment')
                ->with(\Mockery::type('int'), \Mockery::type(ApproveCommentDTO::class), \Mockery::type('int'))
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Test note',
            ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));

        Log::shouldReceive('error')->with(
            'ApproveCommentController: Exception occurred',
            \Mockery::type('array')
        );
    });

    it('handles ModelNotFoundException and returns 404', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) use ($comment) {
            $exception = new ModelNotFoundException;
            $exception->setModel(Comment::class);
            $mock->shouldReceive('approveComment')
                ->with(\Mockery::on(fn ($arg) => $arg instanceof Comment && $arg->id === $comment->id), \Mockery::type(ApproveCommentDTO::class), \Mockery::type(User::class))
                ->andThrow($exception);
        });

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Test note',
            ]);

        expect($response)->toHaveApiErrorStructure(404)
            ->and($response->json('message'))->toBe(__('common.comment_not_found'));
    });

    it('updates comment timestamps when approved', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
            'updated_at' => now()->subDay(),
        ]);

        $originalUpdatedAt = $comment->updated_at;

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved',
            ]);

        expect($response->getStatusCode())->toBe(200);
        $comment->refresh();
        expect($comment->updated_at->timestamp)->toBeGreaterThan($originalUpdatedAt->timestamp);
    });

    it('maintains other comment fields when approving', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalContent = 'Original content';
        $userId = User::factory()->create()->id;
        $articleId = Article::factory()->create()->id;
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
            'content' => $originalContent,
            'user_id' => $userId,
            'article_id' => $articleId,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved',
            ]);

        expect($response->getStatusCode())->toBe(200);
        $comment->refresh();
        expect($comment->content)->toBe($originalContent)
            ->and($comment->user_id)->toBe($userId)
            ->and($comment->article_id)->toBe($articleId);
    });

    it('dispatches CommentApprovedEvent when comment is approved', function () {
        Event::fake([CommentApprovedEvent::class]);
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved',
            ]);

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(CommentApprovedEvent::class, fn ($event) => $event->comment->id === $comment->id
            && $event->comment->status === CommentStatus::APPROVED);
    });

    it('creates notification for comment author when comment is approved', function () {
        // Reset event fake by faking an event that won't be dispatched in this test
        // This resets the global fake and allows all other events to be dispatched
        // With QUEUE_CONNECTION=sync, queued listeners run immediately
        Event::fake([\App\Events\Comment\CommentCreatedEvent::class]);

        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $articleAuthor = User::factory()->create();
        $commenter = User::factory()->create();
        $article = Article::factory()->for($articleAuthor, 'author')->create();
        $comment = Comment::factory()->create([
            'article_id' => $article->id,
            'user_id' => $commenter->id,
            'status' => CommentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved',
            ]);

        expect($response->getStatusCode())->toBe(200);

        // Verify notification was created for comment author (not article author)
        $notification = Notification::where('type', NotificationType::SYSTEM_ALERT->value)
            ->whereJsonContains('message->title', __('notifications.comment_approved.title'))
            ->first();

        expect($notification)->not->toBeNull();

        // Verify user notification was created for the comment author
        $userNotification = UserNotification::where('user_id', $commenter->id)
            ->where('notification_id', $notification->id)
            ->first();

        expect($userNotification)->not->toBeNull()
            ->and($userNotification->is_read)->toBeFalse();
    });

    it('does not create notification when comment author is the same as article author', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $author = User::factory()->create();
        $article = Article::factory()->for($author, 'author')->create();
        $comment = Comment::factory()->create([
            'article_id' => $article->id,
            'user_id' => $author->id, // Same user as article author
            'status' => CommentStatus::PENDING->value,
        ]);

        $notificationCountBefore = Notification::count();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.comments.approve', $comment), [
                'admin_note' => 'Approved',
            ]);

        expect($response->getStatusCode())->toBe(200);

        // Verify no notification was created
        expect(Notification::count())->toBe($notificationCountBefore);
    });
});
