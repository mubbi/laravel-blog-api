<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Events\Article\ArticleFeaturedEvent;
use App\Events\Article\ArticlePinnedEvent;
use App\Events\Article\ArticleUnfeaturedEvent;
use App\Events\Article\ArticleUnpinnedEvent;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Interfaces\ArticleFeatureServiceInterface;
use App\Services\Interfaces\ArticleManagementServiceInterface;
use App\Services\Interfaces\CacheServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ArticleFeatureService implements ArticleFeatureServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleManagementServiceInterface $articleManagementService,
        private readonly CacheServiceInterface $cacheService
    ) {}

    /**
     * Feature an article (using route model binding)
     */
    public function featureArticle(Article $article): Article
    {
        try {
            $newFeaturedStatus = ! $article->is_featured;

            $this->articleRepository->update($article->id, [
                'is_featured' => $newFeaturedStatus,
                'featured_at' => $newFeaturedStatus ? now() : null,
            ]);

            $article->refresh();
            $freshArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

            // Invalidate article cache
            $this->invalidateArticleCache($article);

            if ($newFeaturedStatus) {
                Event::dispatch(new ArticleFeaturedEvent($freshArticle));
            } else {
                Event::dispatch(new ArticleUnfeaturedEvent($freshArticle));
            }

            return $freshArticle;
        } catch (Throwable $e) {
            Log::error(__('log.feature_article_error'), [
                'id' => $article->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Unfeature an article
     */
    public function unfeatureArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'is_featured' => false,
            'featured_at' => null,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleUnfeaturedEvent($article));

        return $article;
    }

    /**
     * Pin an article (using route model binding)
     */
    public function pinArticle(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticlePinnedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Unpin an article (using route model binding)
     */
    public function unpinArticle(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleUnpinnedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Invalidate article cache by slug and ID
     */
    private function invalidateArticleCache(Article $article): void
    {
        $this->cacheService->forget(CacheKeys::articleBySlug($article->slug));
        $this->cacheService->forget(CacheKeys::articleById($article->id));
    }
}
