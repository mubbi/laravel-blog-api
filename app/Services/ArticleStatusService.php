<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Events\Article\ArticleApprovedEvent;
use App\Events\Article\ArticleArchivedEvent;
use App\Events\Article\ArticleDeletedEvent;
use App\Events\Article\ArticleRejectedEvent;
use App\Events\Article\ArticleRestoredEvent;
use App\Events\Article\ArticleRestoredFromTrashEvent;
use App\Events\Article\ArticleTrashedEvent;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Facades\Event;

final class ArticleStatusService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Approve an article
     */
    public function approveArticle(int $id, int $approvedBy): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::PUBLISHED,
            'approved_by' => $approvedBy,
            'published_at' => now(),
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleApprovedEvent($article));

        return $article;
    }

    /**
     * Reject an article (set to draft)
     */
    public function rejectArticle(int $id, int $rejectedBy): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::DRAFT,
            'approved_by' => $rejectedBy,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleRejectedEvent($article));

        return $article;
    }

    /**
     * Archive an article
     */
    public function archiveArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::ARCHIVED,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleArchivedEvent($article));

        return $article;
    }

    /**
     * Restore an article from archive
     */
    public function restoreArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleRestoredEvent($article));

        return $article;
    }

    /**
     * Trash an article
     */
    public function trashArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::TRASHED,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleTrashedEvent($article));

        return $article;
    }

    /**
     * Restore an article from trash
     */
    public function restoreFromTrash(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::DRAFT,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleRestoredFromTrashEvent($article));

        return $article;
    }

    /**
     * Permanently delete an article
     */
    public function deleteArticle(int $id): bool
    {
        $article = $this->articleRepository->findOrFail($id);
        $deleted = $this->articleRepository->delete($id);

        if ($deleted) {
            Event::dispatch(new ArticleDeletedEvent($article));
        }

        return $deleted;
    }
}
