<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Comment\CommentUpdatedEvent;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/UpdateCommentController', function () {
    it('can update own comment successfully', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Original content',
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
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
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'content' => 'Updated content',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);

        Event::assertDispatched(CommentUpdatedEvent::class);
    });

    it('admin can update any comment', function () {
        // Arrange
        Event::fake();
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);
        $token = $admin->createToken('test-token', ['access-api']);

        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'content' => 'Original content',
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated by admin',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'content' => 'Updated by admin',
                ],
            ]);

        Event::assertDispatched(CommentUpdatedEvent::class);
    });

    it('returns 403 when user tries to update another user comment', function () {
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
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
        ]);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 422 when content is missing', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), []);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field is required.',
            ]);
    });

    it('returns 422 when content exceeds max length', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => str_repeat('a', 5001),
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field must not be greater than 5000 characters.',
            ]);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $comment = Comment::factory()->create();

        // Act
        $response = $this->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
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
        ])->putJson(route('api.v1.comments.update', 99999), [
            'content' => 'Updated content',
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

        // Mock CommentService to throw exception
        $this->mock(CommentService::class, function ($mock) {
            $mock->shouldReceive('updateComment')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'Updated content',
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

    it('updates comment with minimum content length', function () {
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
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => 'A',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'content' => 'A',
                ],
            ]);
    });

    it('updates comment with maximum content length', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
        ]);
        $maxContent = str_repeat('a', 5000);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.comments.update', $comment), [
            'content' => $maxContent,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'content' => $maxContent,
                ],
            ]);
    });
});
