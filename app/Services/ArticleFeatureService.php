<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Article\ArticleFeaturedEvent;
use App\Events\Article\ArticlePinnedEvent;
use App\Events\Article\ArticleUnfeaturedEvent;
use App\Events\Article\ArticleUnpinnedEvent;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ArticleFeatureService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Feature an article
     */
    public function featureArticle(int $id): Article
    {
        try {
            $article = $this->articleRepository->findOrFail($id);
            $newFeaturedStatus = ! $article->is_featured;

            $this->articleRepository->update($id, [
                'is_featured' => $newFeaturedStatus,
                'featured_at' => $newFeaturedStatus ? now() : null,
            ]);

            $freshArticle = $this->articleManagementService->getArticleWithRelationships($id);

            if ($newFeaturedStatus) {
                Event::dispatch(new ArticleFeaturedEvent($freshArticle));
            } else {
                Event::dispatch(new ArticleUnfeaturedEvent($freshArticle));
            }

            return $freshArticle;
        } catch (Throwable $e) {
            Log::error('FeatureArticle error', [
                'id' => $id,
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

        Event::dispatch(new ArticleUnfeaturedEvent($article));

        return $article;
    }

    /**
     * Pin an article
     */
    public function pinArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticlePinnedEvent($article));

        return $article;
    }

    /**
     * Unpin an article
     */
    public function unpinArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleUnpinnedEvent($article));

        return $article;
    }
}
