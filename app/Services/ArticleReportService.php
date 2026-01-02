<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ReportArticleDTO;
use App\Events\Article\ArticleReportedEvent;
use App\Events\Article\ArticleReportsClearedEvent;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Support\Facades\Event;

final class ArticleReportService
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

        Event::dispatch(new ArticleReportsClearedEvent($updatedArticle));

        return $updatedArticle;
    }
}
