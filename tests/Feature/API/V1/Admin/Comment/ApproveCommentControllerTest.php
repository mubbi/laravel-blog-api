<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\UserRole;
use App\Events\Comment\CommentApprovedEvent;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('API/V1/Admin/Comment/ApproveCommentController', function () {
    it('can approve a pending comment successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Approved after review',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
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
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'status' => CommentStatus::APPROVED->value,
                ],
            ]);

        // Verify database update
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::APPROVED->value,
            'admin_note' => 'Approved after review',
        ]);
    });

    it('can approve a comment without admin note', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'status' => CommentStatus::APPROVED->value,
                ],
            ]);

        // Verify database update
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::APPROVED->value,
        ]);
    });

    it('can approve an already approved comment', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Re-approved',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'status' => CommentStatus::APPROVED->value,
                ],
            ]);
    });

    it('can approve a rejected comment', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::REJECTED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Approved after reconsideration',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'status' => CommentStatus::APPROVED->value,
                ],
            ]);
    });

    it('returns 404 when comment does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $nonExistentId = 99999;

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $nonExistentId), [
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
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
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
        $response = $this->postJson(route('api.v1.admin.comments.approve', $comment->id), [
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
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
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
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Mock CommentService to throw exception
        $this->mock(CommentService::class, function ($mock) {
            $mock->shouldReceive('approveComment')
                ->with(\Mockery::type('int'), \Mockery::type(\App\Data\ApproveCommentDTO::class))
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);

        // Verify error was logged
        Log::shouldReceive('error')->with(
            'Comment approval failed',
            \Mockery::type('array')
        );
    });

    it('handles ModelNotFoundException and returns 404', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Mock CommentService to throw ModelNotFoundException
        $this->mock(CommentService::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\Comment::class);
            $mock->shouldReceive('approveComment')
                ->with(\Mockery::type('int'), \Mockery::type(\App\Data\ApproveCommentDTO::class))
                ->andThrow($exception);
        });

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
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

    it('updates comment timestamps when approved', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
            'updated_at' => now()->subDay(),
        ]);

        $originalUpdatedAt = $comment->updated_at;

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Approved',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify timestamp was updated
        $comment->refresh();
        expect($comment->updated_at->timestamp)->toBeGreaterThan($originalUpdatedAt->timestamp);
    });

    it('maintains other comment fields when approving', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
            'content' => 'Original content',
            'user_id' => User::factory()->create()->id,
            'article_id' => \App\Models\Article::factory()->create()->id,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Approved',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify other fields remain unchanged
        $comment->refresh();
        expect($comment->content)->toBe('Original content');
        expect($comment->user_id)->toBe($comment->user_id);
        expect($comment->article_id)->toBe($comment->article_id);
    });

    it('dispatches CommentApprovedEvent when comment is approved', function () {
        // Arrange
        Event::fake([CommentApprovedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.comments.approve', $comment->id), [
                'admin_note' => 'Approved',
            ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(CommentApprovedEvent::class, function ($event) use ($comment) {
            return $event->comment->id === $comment->id
                && $event->comment->status === CommentStatus::APPROVED;
        });
    });
});
