<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\CommentService;

describe('API/V1/Comment/GetOwnCommentsController', function () {
    it('can get own comments successfully', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->count(5)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'comments' => [
                        '*' => [
                            'id',
                            'content',
                            'status',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'from',
                        'last_page',
                        'per_page',
                        'to',
                        'total',
                    ],
                ],
            ])
            ->assertJson([
                'status' => true,
            ]);

        $responseData = $response->json('data.comments');
        $this->assertCount(5, $responseData);
    });

    it('only returns comments belonging to authenticated user', function () {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        Comment::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(3, $responseData);

        foreach ($responseData as $comment) {
            $this->assertEquals($user->id, $comment['user_id']);
        }
    });

    it('returns comments ordered by created_at desc', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        $oldComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'created_at' => now()->subDays(5),
        ]);
        $newComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertEquals($newComment->id, $responseData[0]['id']);
        $this->assertEquals($oldComment->id, $responseData[1]['id']);
    });

    it('can paginate comments with custom per_page', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->count(25)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own', ['per_page' => 10]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(10, $responseData);

        $meta = $response->json('data.meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(3, $meta['last_page']);
    });

    it('can paginate to different pages', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->count(25)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own', ['page' => 2, 'per_page' => 10]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(10, $responseData);

        $meta = $response->json('data.meta');
        $this->assertEquals(2, $meta['current_page']);
    });

    it('returns all comment statuses for own comments', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::PENDING,
        ]);
        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::APPROVED,
        ]);
        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::REJECTED,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(3, $responseData);

        $statuses = array_column($responseData, 'status');
        $this->assertContains(CommentStatus::PENDING->value, $statuses);
        $this->assertContains(CommentStatus::APPROVED->value, $statuses);
        $this->assertContains(CommentStatus::REJECTED->value, $statuses);
    });

    it('returns 401 when not authenticated', function () {
        // Act
        $response = $this->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 422 when page is less than 1', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own', ['page' => 0]));

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ]);
    });

    it('returns 422 when per_page exceeds max', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own', ['per_page' => 101]));

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The per page field must not be greater than 100.',
            ]);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Mock CommentService to throw exception
        $this->mock(CommentService::class, function ($mock) {
            $mock->shouldReceive('getOwnComments')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('handles empty results', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(0, $responseData);

        $meta = $response->json('data.meta');
        $this->assertEquals(0, $meta['total']);
    });

    it('uses default pagination values when not provided', function () {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api']);
        $article = Article::factory()->create();

        Comment::factory()->count(20)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.comments.own'));

        // Assert
        $response->assertStatus(200);

        $meta = $response->json('data.meta');
        $this->assertEquals(15, $meta['per_page']);
        $this->assertEquals(1, $meta['current_page']);
    });
});
