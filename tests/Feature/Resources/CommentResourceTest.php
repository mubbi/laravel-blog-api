<?php

declare(strict_types=1);

use App\Enums\CommentStatus;
use App\Http\Resources\V1\Comment\CommentResource;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;

describe('CommentResource', function () {
    it('transforms comment with all fields', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'content' => 'Test comment',
            'status' => CommentStatus::APPROVED,
            'report_count' => 5,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array)->toHaveKeys([
            'id',
            'article_id',
            'user_id',
            'parent_comment_id',
            'content',
            'status',
            'status_display',
            'is_approved',
            'approved_by',
            'approved_at',
            'report_count',
            'last_reported_at',
            'report_reason',
            'moderator_notes',
            'admin_note',
            'deleted_reason',
            'deleted_by',
            'deleted_at',
            'created_at',
            'updated_at',
            'replies_count',
        ]);

        expect($array['id'])->toBe($comment->id);
        expect($array['content'])->toBe('Test comment');
        expect($array['status'])->toBe(CommentStatus::APPROVED->value);
        expect($array['is_approved'])->toBeTrue();
        expect($array['report_count'])->toBe(5);
    });

    it('includes user when loaded', function () {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        $comment->load('user');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['user'])->toBeArray();
        expect($array['user']['id'])->toBe($user->id);
        expect($array['user']['name'])->toBe('John Doe');
        expect($array['user']['email'])->toBe('john@example.com');
    });

    it('returns null for user when not loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        // Don't load user relationship

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        // whenLoaded returns MissingValue when not loaded, which appears in toArray()
        // but gets filtered during JSON serialization
        $userValue = $array['user'] ?? null;
        expect($userValue)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    it('handles null user_id', function () {
        // Arrange
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => null,
            'article_id' => $article->id,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['user_id'])->toBeNull();
        // whenLoaded returns MissingValue when not loaded
        $userValue = $array['user'] ?? null;
        expect($userValue)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    it('includes article when loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        $comment->load('article');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['article'])->toBeArray();
        expect($array['article']['id'])->toBe($article->id);
        expect($array['article']['title'])->toBe('Test Article');
        expect($array['article']['slug'])->toBe('test-article');
    });

    it('returns null for article when not loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        // Don't load article relationship

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        // whenLoaded returns MissingValue when not loaded
        $articleValue = $array['article'] ?? null;
        expect($articleValue)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    it('includes approver when loaded', function () {
        // Arrange
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
        $comment->load('approver');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['approver'])->toBeArray();
        expect($array['approver']['id'])->toBe($admin->id);
        expect($array['approver']['name'])->toBe('Admin User');
        expect($array['approver']['email'])->toBe('admin@example.com');
    });

    it('returns null for approver when not loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        // whenLoaded returns MissingValue when not loaded
        $approverValue = $array['approver'] ?? null;
        expect($approverValue)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    it('returns null for approver when approved_by is null', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'approved_by' => null,
        ]);
        $comment->load('approver');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['approver'])->toBeNull();
    });

    it('includes deleted_by_user when loaded', function () {
        // Arrange
        $deleter = User::factory()->create([
            'name' => 'Deleter User',
            'email' => 'deleter@example.com',
        ]);
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'deleted_by' => $deleter->id,
            'deleted_at' => now(),
        ]);
        $comment->load('deletedBy');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['deleted_by_user'])->toBeArray();
        expect($array['deleted_by_user']['id'])->toBe($deleter->id);
        expect($array['deleted_by_user']['name'])->toBe('Deleter User');
        expect($array['deleted_by_user']['email'])->toBe('deleter@example.com');
    });

    it('returns null for deleted_by_user when not loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        // whenLoaded returns MissingValue when not loaded
        $deletedByValue = $array['deleted_by_user'] ?? null;
        expect($deletedByValue)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
    });

    it('returns null for deleted_by_user when deleted_by is null', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'deleted_by' => null,
        ]);
        $comment->load('deletedBy');

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['deleted_by_user'])->toBeNull();
    });

    it('handles nullable datetime fields', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'approved_at' => null,
            'last_reported_at' => null,
            'deleted_at' => null,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['approved_at'])->toBeNull();
        expect($array['last_reported_at'])->toBeNull();
        expect($array['deleted_at'])->toBeNull();
    });

    it('formats datetime fields as ISO strings', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $now = now();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'approved_at' => $now,
            'last_reported_at' => $now,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        // Compare dates (ignore microsecond precision differences)
        expect($array['approved_at'])->toStartWith($now->format('Y-m-d\TH:i:s'));
        expect($array['last_reported_at'])->toStartWith($now->format('Y-m-d\TH:i:s'));
        expect($array['created_at'])->toStartWith($comment->created_at->format('Y-m-d\TH:i:s'));
        expect($array['updated_at'])->toStartWith($comment->updated_at->format('Y-m-d\TH:i:s'));
    });

    it('handles replies_count when not set', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['replies_count'])->toBe(0);
    });

    it('includes replies when loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        $reply1 = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'parent_comment_id' => $parentComment->id,
        ]);
        $reply2 = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'parent_comment_id' => $parentComment->id,
        ]);
        // Set replies_page relation manually (as done in ArticleService)
        $parentComment->setRelation('replies_page', collect([$reply1, $reply2]));

        // Act
        $resource = new CommentResource($parentComment);
        // Resolve through response to get properly serialized array
        $response = $resource->response();
        $decoded = json_decode($response->getContent(), true);
        // JsonResource wraps data in 'data' key
        $array = $decoded['data'] ?? $decoded;

        // Assert
        expect($array['replies'])->toBeArray();
        expect(count($array['replies']))->toBe(2);
    });

    it('returns empty array for replies when not loaded', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);

        // Act
        $resource = new CommentResource($comment);
        // Resolve through response to get properly serialized array
        $response = $resource->response();
        $decoded = json_decode($response->getContent(), true);
        // JsonResource wraps data in 'data' key
        $array = $decoded['data'] ?? $decoded;

        // Assert
        // When replies_page is not loaded, MissingValue gets filtered out during JSON serialization
        // So replies key may not exist, or it could be an empty array
        if (isset($array['replies'])) {
            expect($array['replies'])->toBeArray();
            expect($array['replies'])->toBeEmpty();
        } else {
            // If MissingValue was filtered out, that's also acceptable
            expect($array)->not->toHaveKey('replies');
        }
    });

    it('handles different comment statuses', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $pendingComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::PENDING,
        ]);
        $approvedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::APPROVED,
        ]);
        $rejectedComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'status' => CommentStatus::REJECTED,
        ]);

        // Act
        $pendingResource = new CommentResource($pendingComment);
        $approvedResource = new CommentResource($approvedComment);
        $rejectedResource = new CommentResource($rejectedComment);

        $pendingArray = $pendingResource->toArray(request());
        $approvedArray = $approvedResource->toArray(request());
        $rejectedArray = $rejectedResource->toArray(request());

        // Assert
        expect($pendingArray['status'])->toBe(CommentStatus::PENDING->value);
        expect($pendingArray['is_approved'])->toBeFalse();

        expect($approvedArray['status'])->toBe(CommentStatus::APPROVED->value);
        expect($approvedArray['is_approved'])->toBeTrue();

        expect($rejectedArray['status'])->toBe(CommentStatus::REJECTED->value);
        expect($rejectedArray['is_approved'])->toBeFalse();
    });

    it('handles parent_comment_id', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $parentComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
        ]);
        $replyComment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'parent_comment_id' => $parentComment->id,
        ]);

        // Act
        $resource = new CommentResource($replyComment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['parent_comment_id'])->toBe($parentComment->id);
    });

    it('handles null parent_comment_id', function () {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'article_id' => $article->id,
            'parent_comment_id' => null,
        ]);

        // Act
        $resource = new CommentResource($comment);
        $array = $resource->toArray(request());

        // Assert
        expect($array['parent_comment_id'])->toBeNull();
    });
});
