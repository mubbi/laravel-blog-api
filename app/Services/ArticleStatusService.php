<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Enums\ArticleStatus;
use App\Events\Article\ArticleApprovedEvent;
use App\Events\Article\ArticleArchivedEvent;
use App\Events\Article\ArticleDeletedEvent;
use App\Events\Article\ArticleRejectedEvent;
use App\Events\Article\ArticleRestoredEvent;
use App\Events\Article\ArticleRestoredFromTrashEvent;
use App\Events\Article\ArticleTrashedEvent;
use App\Models\Article;
use App\Models\User;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use App\Services\Interfaces\ArticleStatusServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

final class ArticleStatusService implements ArticleStatusServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleManagementServiceInterface $articleManagementService
    ) {}

    /**
     * Approve an article (using route model binding)
     */
    public function approveArticle(Article $article, User $approvedBy): Article
    {
        $this->articleRepository->update($article->id, [
            'status' => ArticleStatus::PUBLISHED,
            'approved_by' => $approvedBy->id,
            'published_at' => now(),
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleApprovedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Reject an article (set to draft)
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function rejectArticle(int $id, int $rejectedBy): Article
    {
        $article = $this->articleRepository->findOrFail($id);

        $this->articleRepository->update($id, [
            'status' => ArticleStatus::DRAFT,
            'approved_by' => $rejectedBy,
        ]);

        $updatedArticle = $this->articleManagementService->getArticleWithRelationships($id);

        // Invalidate article cache
        $this->invalidateArticleCache($updatedArticle);

        Event::dispatch(new ArticleRejectedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Archive an article (using route model binding)
     */
    public function archiveArticle(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'status' => ArticleStatus::ARCHIVED,
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleArchivedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Restore an article from archive (using route model binding)
     */
    public function restoreArticle(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleRestoredEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Trash an article (using route model binding)
     */
    public function trashArticle(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'status' => ArticleStatus::TRASHED,
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleTrashedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Restore an article from trash (using route model binding)
     */
    public function restoreFromTrash(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'status' => ArticleStatus::DRAFT,
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleRestoredFromTrashEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Permanently delete an article
     */
    public function deleteArticle(int $id): bool
    {
        $article = $this->articleRepository->findById($id);

        if ($article === null) {
            return false;
        }

        // Invalidate article cache before deletion
        $this->invalidateArticleCache($article);

        $deleted = $this->articleRepository->delete($id);

        if ($deleted) {
            Event::dispatch(new ArticleDeletedEvent($article));
        }

        return $deleted;
    }

    /**
     * Invalidate article cache by slug and ID
     */
    private function invalidateArticleCache(Article $article): void
    {
        Cache::forget(CacheKeys::articleBySlug($article->slug));
        Cache::forget(CacheKeys::articleById($article->id));
    }
}
