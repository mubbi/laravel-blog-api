<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Data\ReportArticleDTO;
use App\Events\Article\ArticleReportedEvent;
use App\Events\Article\ArticleReportsClearedEvent;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Services\Interfaces\ArticleReportServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

final class ArticleReportService implements ArticleReportServiceInterface
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ArticleManagementService $articleManagementService
    ) {}

    /**
     * Report an article (using route model binding)
     */
    public function reportArticle(Article $article, ReportArticleDTO $dto): Article
    {
        $this->articleRepository->update($article->id, [
            'report_count' => $article->report_count + 1,
            'last_reported_at' => now(),
            'report_reason' => $dto->getReason(),
        ]);

        $article->refresh();
        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleReportedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Clear article reports (using route model binding)
     */
    public function clearArticleReports(Article $article): Article
    {
        $this->articleRepository->update($article->id, [
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
        ]);

        $updatedArticle = $this->articleManagementService->loadArticleRelationshipsOnModel($article);

        // Invalidate article cache
        $this->invalidateArticleCache($article);

        Event::dispatch(new ArticleReportsClearedEvent($updatedArticle));

        return $updatedArticle;
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
