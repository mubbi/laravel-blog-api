<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/DeleteCommentController', function () {
    it('can delete own comment successfully', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'No longer needed',
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
                'message' => __('common.comment_deleted_successfully'),
                'data' => null,
            ]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);

        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('can delete own comment without reason', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('common.comment_deleted_successfully'),
            ]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);

        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('admin can delete any comment', function () {
        // Arrange
        Event::fake();
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);
        $token = $admin->createToken('test-token', ['access-api']);

        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Deleted by admin',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);

        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('returns 403 when user tries to delete another user comment', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 422 when reason exceeds max length', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => str_repeat('a', 501),
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 500 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $comment = Comment::factory()->create();

        // Act
        $response = $this->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(401);
    });

    it('returns 404 when comment does not exist', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', 99999), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(404);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Mock CommentServiceInterface to throw exception
        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('deleteComment')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('permanently deletes comment from database', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        $commentId = $comment->id;

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->deleteJson(route('api.v1.comments.destroy', $comment));

        // Assert
        $response->assertStatus(200);

        // Verify comment is completely removed
        $this->assertDatabaseMissing('comments', [
            'id' => $commentId,
        ]);

        $deletedComment = Comment::withTrashed()->find($commentId);
        expect($deletedComment)->toBeNull();
    });
});
