<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Comment/GetCommentsController', function () {
    it('can get paginated comments', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->count(15)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'comments' => [
                        '*' => [
                            'id', 'content', 'status', 'status_display', 'is_approved',
                            'approved_by', 'approved_at', 'report_count', 'created_at', 'updated_at',
                            'user' => [
                                'id', 'name', 'email',
                            ],
                            'article' => [
                                'id', 'title', 'slug',
                            ],
                        ],
                    ],
                    'meta' => [
                        'current_page', 'from', 'last_page', 'per_page', 'to', 'total',
                    ],
                ],
            ]);

        $responseData = $response->json('data.comments');
        $this->assertCount(15, $responseData);
    });

    it('can filter comments by status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->count(5)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::PENDING,
        ]);

        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::APPROVED,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index', ['status' => CommentStatus::PENDING->value]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(5, $responseData);

        foreach ($responseData as $comment) {
            $this->assertEquals(CommentStatus::PENDING->value, $comment['status']);
        }
    });

    it('can filter comments by user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->count(3)->create([
            'user_id' => $user1->id,
            'article_id' => $article->id,
        ]);

        Comment::factory()->count(2)->create([
            'user_id' => $user2->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index', ['user_id' => $user1->id]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(3, $responseData);

        foreach ($responseData as $comment) {
            $this->assertEquals($user1->id, $comment['user']['id']);
        }
    });

    it('can filter comments by article', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        Comment::factory()->count(4)->create([
            'user_id' => $user->id,
            'article_id' => $article1->id,
        ]);

        Comment::factory()->count(2)->create([
            'user_id' => $user->id,
            'article_id' => $article2->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index', ['article_id' => $article1->id]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(4, $responseData);

        foreach ($responseData as $comment) {
            $this->assertEquals($article1->id, $comment['article']['id']);
        }
    });

    it('can search comments by content', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'content' => 'This is a test comment with specific content',
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'content' => 'Another comment with different content',
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index', ['search' => 'specific']));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(1, $responseData);
        $this->assertStringContainsString('specific', $responseData[0]['content']);
    });

    it('can sort comments by created_at in descending order', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
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
        ])->getJson(route('api.v1.admin.comments.index', ['sort_by' => 'created_at', 'sort_order' => 'desc']));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertEquals($newComment->id, $responseData[0]['id']);
        $this->assertEquals($oldComment->id, $responseData[1]['id']);
    });

    it('can sort comments by created_at in ascending order', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
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
        ])->getJson(route('api.v1.admin.comments.index', ['sort_by' => 'created_at', 'sort_order' => 'asc']));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertEquals($oldComment->id, $responseData[0]['id']);
        $this->assertEquals($newComment->id, $responseData[1]['id']);
    });

    it('can paginate comments with custom per_page', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->count(25)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index', ['per_page' => 10]));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(10, $responseData);

        $meta = $response->json('data.meta');
        $this->assertEquals(10, $meta['per_page']);
        $this->assertEquals(25, $meta['total']);
        $this->assertEquals(3, $meta['last_page']);
    });

    it('returns 401 when user is not authenticated', function () {
        // Act
        $response = $this->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(403);
    });

    it('includes user and article information in response', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);

        Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'content' => 'Test comment content',
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(1, $responseData);

        $comment = $responseData[0];
        $this->assertEquals('Test comment content', $comment['content']);
        $this->assertEquals('John Doe', $comment['user']['name']);
        $this->assertEquals('john@example.com', $comment['user']['email']);
        $this->assertEquals('Test Article', $comment['article']['title']);
        $this->assertEquals('test-article', $comment['article']['slug']);
    });

    it('handles empty results', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(200);

        $responseData = $response->json('data.comments');
        $this->assertCount(0, $responseData);

        $meta = $response->json('data.meta');
        $this->assertEquals(0, $meta['total']);
    });

    it('returns 500 when service throws exception', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Mock service to throw exception
        $this->mock(\App\Services\CommentService::class, function ($mock) {
            $mock->shouldReceive('getComments')
                ->andThrow(new \Exception('Service error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.comments.index'));

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
