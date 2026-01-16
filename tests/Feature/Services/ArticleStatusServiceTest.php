<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Events\Article\ArticleArchivedEvent;
use App\Events\Article\ArticleDeletedEvent;
use App\Events\Article\ArticleRejectedEvent;
use App\Events\Article\ArticleRestoredEvent;
use App\Events\Article\ArticleRestoredFromTrashEvent;
use App\Events\Article\ArticleTrashedEvent;
use App\Models\Article;
use App\Models\User;
use App\Services\ArticleStatusService;
use Illuminate\Support\Facades\Event;

describe('ArticleStatusService', function () {
    beforeEach(function () {
        $this->service = app(ArticleStatusService::class);
    });

    describe('archiveArticle', function () {
        it('archives an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'status' => ArticleStatus::PUBLISHED,
            ]);

            // Act
            $result = $this->service->archiveArticle($article);

            // Assert
            expect($result->status)->toBe(ArticleStatus::ARCHIVED);
            Event::assertDispatched(ArticleArchivedEvent::class);
        });
    });

    describe('restoreArticle', function () {
        it('restores an archived article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'status' => ArticleStatus::ARCHIVED,
            ]);

            // Act
            $result = $this->service->restoreArticle($article);

            // Assert
            expect($result->status)->toBe(ArticleStatus::PUBLISHED);
            Event::assertDispatched(ArticleRestoredEvent::class);
        });
    });

    describe('trashArticle', function () {
        it('trashes an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'status' => ArticleStatus::PUBLISHED,
            ]);

            // Act
            $result = $this->service->trashArticle($article);

            // Assert
            expect($result->status)->toBe(ArticleStatus::TRASHED);
            Event::assertDispatched(ArticleTrashedEvent::class);
        });
    });

    describe('restoreFromTrash', function () {
        it('restores a trashed article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create([
                'status' => ArticleStatus::TRASHED,
            ]);

            // Act
            $result = $this->service->restoreFromTrash($article);

            // Assert
            expect($result->status)->toBe(ArticleStatus::DRAFT);
            Event::assertDispatched(ArticleRestoredFromTrashEvent::class);
        });
    });

    describe('rejectArticle', function () {
        it('rejects an article successfully', function () {
            // Arrange
            Event::fake();
            $admin = User::factory()->create();
            $article = Article::factory()->create([
                'status' => ArticleStatus::REVIEW,
            ]);

            // Act
            $result = $this->service->rejectArticle($article->id, $admin->id);

            // Assert
            expect($result->status)->toBe(ArticleStatus::DRAFT);
            expect($result->approved_by)->toBe($admin->id);
            Event::assertDispatched(ArticleRejectedEvent::class);
        });
    });

    describe('deleteArticle', function () {
        it('permanently deletes an article successfully', function () {
            // Arrange
            Event::fake();
            $article = Article::factory()->create();

            // Act
            $result = $this->service->deleteArticle($article->id);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('articles', ['id' => $article->id]);
            Event::assertDispatched(ArticleDeletedEvent::class);
        });

        it('returns false when article does not exist', function () {
            // Act
            $result = $this->service->deleteArticle(99999);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
