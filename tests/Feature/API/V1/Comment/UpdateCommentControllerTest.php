<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Comment\CommentUpdatedEvent;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/UpdateCommentController', function () {
    it('can update own comment successfully', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
            'content' => 'Original content',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'content',
            'status',
            'created_at',
            'updated_at',
        ])->and($response->json('data.id'))->toBe($comment->id)
            ->and($response->json('data.content'))->toBe('Updated content');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);

        Event::assertDispatched(CommentUpdatedEvent::class);
    });

    it('admin can update any comment', function () {
        Event::fake();
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'Original content',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated by admin',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe('Updated by admin');

        Event::assertDispatched(CommentUpdatedEvent::class);
    });

    it('returns 403 when user tries to update another user comment', function () {
        $auth = createAuthenticatedUser();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(403);
    });

    it('returns 422 when content is missing', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field is required.',
            ]);
    });

    it('returns 422 when content exceeds max length', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => str_repeat('a', 5001),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field must not be greater than 5000 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        $comment = Comment::factory()->create();

        $response = $this->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(401);
    });

    it('returns 404 when comment does not exist', function () {
        $auth = createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', 99999), [
            'content' => 'Updated content',
        ]);

        $response->assertStatus(404);
    });

    it('returns 500 when service throws exception', function () {
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('updateComment')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('updates comment with minimum content length', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'A',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe('A');
    });

    it('updates comment with maximum content length', function () {
        Event::fake();
        $auth = createAuthenticatedUser();
        $comment = Comment::factory()->create([
            'user_id' => $auth['user']->id,
        ]);
        $maxContent = str_repeat('a', 5000);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => $maxContent,
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('data.content'))->toBe($maxContent);
    });
});
