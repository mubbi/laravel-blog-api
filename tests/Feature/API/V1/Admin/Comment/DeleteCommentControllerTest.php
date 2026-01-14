<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\UserRole;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('API/V1/Admin/Comment/DeleteCommentController', function () {
    it('can delete a comment successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted for violation',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted'),
                'data' => null,
            ]);

        // Verify comment was deleted from database
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    });

    it('can delete a pending comment', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted pending comment',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted'),
            ]);

        // Verify comment was deleted
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    });

    it('can delete a rejected comment', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::REJECTED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted rejected comment',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted'),
            ]);

        // Verify comment was deleted
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    });

    it('can delete a comment without admin note', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted'),
            ]);

        // Verify comment was deleted
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
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
            ->deleteJson(route('api.v1.admin.comments.destroy', $nonExistentId), [
                'reason' => 'Test note',
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

    it('returns 403 when user lacks delete_comments permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
            ]);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
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
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => str_repeat('a', 501), // Exceeds max length
            ]);

        // Assert - reason field validation (max 500 characters)
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 500 characters.',
                'data' => null,
                'error' => [
                    'reason' => ['The reason field must not be greater than 500 characters.'],
                ],
            ]);
    });

    it('handles service exception and logs error', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Mock CommentService to throw exception
        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('deleteComment')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
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
            'DeleteCommentController: Exception occurred',
            \Mockery::type('array')
        );
    });

    it('handles ModelNotFoundException and returns 404', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Mock CommentService to throw ModelNotFoundException
        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\Comment::class);
            $mock->shouldReceive('deleteComment')
                ->andThrow($exception);
        });

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
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

    it('permanently deletes comment from database', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'content' => 'Test comment content',
            'user_id' => User::factory()->create()->id,
            'article_id' => \App\Models\Article::factory()->create()->id,
        ]);

        $commentId = $comment->id;

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Permanently deleted',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify comment is completely removed from database
        $this->assertDatabaseMissing('comments', [
            'id' => $commentId,
        ]);

        // Verify no soft-deleted record exists
        $deletedComment = Comment::withTrashed()->find($commentId);
        expect($deletedComment)->toBeNull();
    });

    it('deletes comment with related data', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $user = User::factory()->create();
        $article = \App\Models\Article::factory()->create();

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'content' => 'Test comment with relations',
            'user_id' => $user->id,
            'article_id' => $article->id,
            'admin_note' => 'Previous note',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted with relations',
            ]);

        // Assert
        $response->assertStatus(200);

        // Verify comment is deleted
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);

        // Verify related user and article still exist
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
        ]);
    });

    it('handles deletion of comment with admin notes', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'admin_note' => 'Previous admin note',
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Final deletion note',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted'),
            ]);

        // Verify comment is deleted regardless of previous admin notes
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    });

    it('dispatches CommentDeletedEvent when comment is deleted', function () {
        // Arrange
        Event::fake([CommentDeletedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted for violation',
            ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(CommentDeletedEvent::class, function ($event) use ($comment) {
            return $event->comment->id === $comment->id;
        });
    });
});
