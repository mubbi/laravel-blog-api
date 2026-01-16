<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\UserRole;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

describe('API/V1/Admin/Comment/DeleteCommentController', function () {
    it('can delete a comment successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted for violation',
            ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('common.comment_deleted'))
            ->and($response->json('data'))->toBeNull();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('can delete a pending comment', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::PENDING->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted pending comment',
            ]);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('message'))->toBe(__('common.comment_deleted'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('can delete a rejected comment', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::REJECTED->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted rejected comment',
            ]);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('message'))->toBe(__('common.comment_deleted'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('can delete a comment without admin note', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('message'))->toBe(__('common.comment_deleted'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('returns 404 when comment does not exist', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', 99999), [
                'reason' => 'Test note',
            ]);

        expect($response)->toHaveApiErrorStructure(404)
            ->and($response->json('message'))->toBe(__('common.comment_not_found'));
    });

    it('returns 403 when user lacks delete_comments permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
            ]);

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
            'admin_note' => 'Test note',
        ]);

        $response->assertStatus(401);
    });

    it('validates admin_note field', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => str_repeat('a', 501),
            ]);

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
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('deleteComment')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
            ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));

        Log::shouldReceive('error')->with(
            'DeleteCommentController: Exception occurred',
            \Mockery::type('array')
        );
    });

    it('handles ModelNotFoundException and returns 404', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $exception = new ModelNotFoundException;
            $exception->setModel(\App\Models\Comment::class);
            $mock->shouldReceive('deleteComment')
                ->andThrow($exception);
        });

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Test note',
            ]);

        expect($response)->toHaveApiErrorStructure(404)
            ->and($response->json('message'))->toBe(__('common.comment_not_found'));
    });

    it('permanently deletes comment from database', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'content' => 'Test comment content',
            'user_id' => User::factory()->create()->id,
            'article_id' => \App\Models\Article::factory()->create()->id,
        ]);

        $commentId = $comment->id;

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Permanently deleted',
            ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
        expect(Comment::withTrashed()->find($commentId))->toBeNull();
    });

    it('deletes comment with related data', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create();
        $article = \App\Models\Article::factory()->create();

        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'content' => 'Test comment with relations',
            'user_id' => $user->id,
            'article_id' => $article->id,
            'admin_note' => 'Previous note',
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted with relations',
            ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    });

    it('handles deletion of comment with admin notes', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
            'admin_note' => 'Previous admin note',
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Final deletion note',
            ]);

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('message'))->toBe(__('common.comment_deleted'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    });

    it('dispatches CommentDeletedEvent when comment is deleted', function () {
        Event::fake([CommentDeletedEvent::class]);
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $comment = Comment::factory()->create([
            'status' => CommentStatus::APPROVED->value,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.comments.destroy', $comment), [
                'reason' => 'Deleted for violation',
            ]);

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(CommentDeletedEvent::class, fn ($event) => $event->comment->id === $comment->id);
    });
});
