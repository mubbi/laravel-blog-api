<?php

declare(strict_types=1);

use App\Data\ApproveCommentDTO;
use App\Data\DeleteCommentDTO;
use App\Data\FilterCommentDTO;
use App\Enums\CommentStatus;
use App\Events\Comment\CommentApprovedEvent;
use App\Events\Comment\CommentDeletedEvent;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('CommentService', function () {
    beforeEach(function () {
        $this->service = app(CommentService::class);
    });

    describe('getCommentById', function () {
        it('can get comment by id with relationships', function () {
            // Arrange
            $user = User::factory()->create();
            $article = Article::factory()->create();
            $comment = Comment::factory()->create([
                'user_id' => $user->id,
                'article_id' => $article->id,
            ]);

            // Act
            $result = $this->service->getCommentById($comment->id);

            // Assert
            expect($result->id)->toBe($comment->id);
            expect($result->relationLoaded('user'))->toBeTrue();
            expect($result->relationLoaded('article'))->toBeTrue();
            expect($result->user->id)->toBe($user->id);
            expect($result->article->id)->toBe($article->id);
        });

        it('throws ModelNotFoundException when comment does not exist', function () {
            // Act & Assert
            expect(fn () => $this->service->getCommentById(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getComments', function () {
        it('can get paginated comments', function () {
            // Arrange
            Comment::factory()->count(20)->create();

            $dto = new FilterCommentDTO(
                perPage: 10
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->count())->toBe(10);
            expect($result->total())->toBe(20);
        });

        it('can filter comments by status', function () {
            // Arrange
            Comment::factory()->count(5)->create(['status' => CommentStatus::APPROVED]);
            Comment::factory()->count(3)->create(['status' => CommentStatus::PENDING]);

            $dto = new FilterCommentDTO(
                status: CommentStatus::APPROVED
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->total())->toBe(5);
            foreach ($result->items() as $comment) {
                expect($comment->status)->toBe(CommentStatus::APPROVED);
            }
        });

        it('can filter comments by user', function () {
            // Arrange
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            Comment::factory()->count(3)->create(['user_id' => $user1->id]);
            Comment::factory()->count(2)->create(['user_id' => $user2->id]);

            $dto = new FilterCommentDTO(
                userId: $user1->id
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->total())->toBe(3);
        });

        it('can filter comments by article', function () {
            // Arrange
            $article1 = Article::factory()->create();
            $article2 = Article::factory()->create();
            Comment::factory()->count(4)->create(['article_id' => $article1->id]);
            Comment::factory()->count(2)->create(['article_id' => $article2->id]);

            $dto = new FilterCommentDTO(
                articleId: $article1->id
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->total())->toBe(4);
        });

        it('can search comments by content', function () {
            // Arrange
            Comment::factory()->create(['content' => 'This is a test comment']);
            Comment::factory()->create(['content' => 'Another comment']);

            $dto = new FilterCommentDTO(
                search: 'test'
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->total())->toBe(1);
            expect($result->items()[0]->content)->toContain('test');
        });

        it('can filter comments with reports', function () {
            // Arrange
            Comment::factory()->count(3)->create(['report_count' => 5]);
            Comment::factory()->count(2)->create(['report_count' => 0]);

            $dto = new FilterCommentDTO(
                hasReports: true
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->total())->toBe(3);
        });

        it('can sort comments', function () {
            // Arrange
            $oldComment = Comment::factory()->create(['created_at' => now()->subDays(5)]);
            $newComment = Comment::factory()->create(['created_at' => now()]);

            $dto = new FilterCommentDTO(
                sortBy: 'created_at',
                sortOrder: 'desc'
            );

            // Act
            $result = $this->service->getComments($dto);

            // Assert
            expect($result->items()[0]->id)->toBe($newComment->id);
            expect($result->items()[1]->id)->toBe($oldComment->id);
        });
    });

    describe('approveComment', function () {
        it('approves a comment successfully', function () {
            // Arrange
            Event::fake();
            $admin = User::factory()->create();
            $comment = Comment::factory()->create([
                'status' => CommentStatus::PENDING,
            ]);
            $dto = ApproveCommentDTO::fromArray(['admin_note' => 'Approved']);

            // Act
            $result = $this->service->approveComment($comment, $dto, $admin);

            // Assert
            expect($result->status)->toBe(CommentStatus::APPROVED);
            expect($result->approved_by)->toBe($admin->id);
            expect($result->approved_at)->not->toBeNull();
            Event::assertDispatched(CommentApprovedEvent::class);
        });
    });

    describe('deleteComment', function () {
        it('deletes a comment successfully', function () {
            // Arrange
            Event::fake();
            $admin = User::factory()->create();
            $comment = Comment::factory()->create();
            $dto = DeleteCommentDTO::fromArray(['reason' => 'Deleted']);

            // Act
            $this->service->deleteComment($comment, $dto, $admin);

            // Assert
            $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
            Event::assertDispatched(CommentDeletedEvent::class);
        });
    });
});
