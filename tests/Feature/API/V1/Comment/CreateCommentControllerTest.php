<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Events\Comment\CommentCreatedEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\Interfaces\CommentServiceInterface;
use Illuminate\Support\Facades\Event;

describe('API/V1/Comment/CreateCommentController', function () {
    it('can create a comment successfully', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'This is a test comment',
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
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'content' => 'This is a test comment',
                    'status' => CommentStatus::PENDING->value,
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::PENDING->value,
        ]);

        Event::assertDispatched(CommentCreatedEvent::class);
    });

    it('can create a reply comment successfully', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'article_id' => $article->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'This is a reply',
            'parent_comment_id' => $parentComment->id,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'content' => 'This is a reply',
                    'parent_comment_id' => $parentComment->id,
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply',
            'parent_comment_id' => $parentComment->id,
            'article_id' => $article->id,
        ]);

        Event::assertDispatched(CommentCreatedEvent::class);
    });

    it('returns 422 when content is missing', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), []);

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
        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => str_repeat('a', 5001),
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The content field must not be greater than 5000 characters.',
            ]);
    });

    it('returns 422 when parent_comment_id does not exist', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
            'parent_comment_id' => 99999,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The selected parent comment id is invalid.',
            ]);
    });

    it('returns 500 when parent comment belongs to different article', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'article_id' => $article1->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article2), [
            'content' => 'Test comment',
            'parent_comment_id' => $parentComment->id,
        ]);

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
            ]);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $article = Article::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
        ]);

        // Assert
        $response->assertStatus(401);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        // Mock CommentServiceInterface to throw exception
        $this->mock(CommentServiceInterface::class, function ($mock) {
            $mock->shouldReceive('createComment')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
            'content' => 'Test comment',
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

    it('creates comment with minimum content length', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
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

    it('creates comment with maximum content length', function () {
        // Arrange
        Event::fake();
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();
        $maxContent = str_repeat('a', 5000);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.comments.store', $article), [
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
