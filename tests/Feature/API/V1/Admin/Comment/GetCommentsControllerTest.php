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
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create();
        $article = Article::factory()->create();

        Comment::factory()->count(15)->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index'));

        expect($response)->toHaveApiSuccessStructure([
            'comments' => [
                '*' => [
                    'id', 'content', 'status', 'status_display', 'is_approved',
                    'approved_by', 'approved_at', 'report_count', 'created_at', 'updated_at',
                    'user' => ['id', 'name', 'email'],
                    'article' => ['id', 'title', 'slug'],
                ],
            ],
            'meta' => [
                'current_page', 'from', 'last_page', 'per_page', 'to', 'total',
            ],
        ])->and($response->json('data.comments'))->toHaveCount(15);
    });

    it('can filter comments by status', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['status' => CommentStatus::PENDING->value]));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData)->toHaveCount(5);
        foreach ($responseData as $comment) {
            expect($comment['status'])->toBe(CommentStatus::PENDING->value);
        }
    });

    it('can filter comments by user', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['user_id' => $user1->id]));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData)->toHaveCount(3);
        foreach ($responseData as $comment) {
            expect($comment['user']['id'])->toBe($user1->id);
        }
    });

    it('can filter comments by article', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['article_id' => $article1->id]));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData)->toHaveCount(4);
        foreach ($responseData as $comment) {
            expect($comment['article']['id'])->toBe($article1->id);
        }
    });

    it('can search comments by content', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['search' => 'specific']));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData)->toHaveCount(1)
            ->and(str_contains($responseData[0]['content'], 'specific'))->toBeTrue();
    });

    it('can sort comments by created_at in descending order', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['sort_by' => 'created_at', 'sort_order' => 'desc']));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData[0]['id'])->toBe($newComment->id)
            ->and($responseData[1]['id'])->toBe($oldComment->id);
    });

    it('can sort comments by created_at in ascending order', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.comments.index', ['sort_by' => 'created_at', 'sort_order' => 'asc']));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.comments');
        expect($responseData[0]['id'])->toBe($oldComment->id)
            ->and($responseData[1]['id'])->toBe($newComment->id);
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

        // Mock service interface to throw exception
        $this->mock(\App\Services\Interfaces\CommentServiceInterface::class, function ($mock) {
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
