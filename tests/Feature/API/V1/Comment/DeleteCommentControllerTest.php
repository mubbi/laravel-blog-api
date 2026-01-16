<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/DeleteCommentController', function () {
    it('can delete own comment successfully', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'No longer needed',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('common.comment_deleted_successfully'))
            ->and($response->json('data'))->toBeNull();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('can delete own comment without reason', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('common.comment_deleted_successfully'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('admin can delete any comment', function () {
        Event::fake();
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Deleted by admin',
        ]);

        expect($response)->toHaveApiSuccessStructure();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        Event::assertDispatched(CommentDeletedEvent::class);
    });

    it('returns 403 when user tries to delete another user comment', function () {
        $auth = createAuthenticatedUser();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        $response->assertStatus(403);
    });

    it('returns 422 when reason exceeds max length', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => str_repeat('a', 501),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The reason field must not be greater than 500 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        $comment = Comment::factory()->create();

        $response = $this->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when comment does not exist', function () {
        $auth = createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', 99999), [
            'reason' => 'Test',
        ]);

        $response->assertStatus(404);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('deleteComment')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment), [
            'reason' => 'Test',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('permanently deletes comment from database', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $commentId = $comment->id;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.comments.destroy', $comment));

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
        expect(Comment::withTrashed()->find($commentId))->toBeNull();
    });
});
