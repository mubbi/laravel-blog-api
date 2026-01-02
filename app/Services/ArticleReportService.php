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
     * Report an article
     */
    public function reportArticle(int $id, ReportArticleDTO $dto): Article
    {
        $article = $this->articleRepository->findOrFail($id);

        $this->articleRepository->update($id, [
            'report_count' => $article->report_count + 1,
            'last_reported_at' => now(),
            'report_reason' => $dto->getReason(),
        ]);

        $updatedArticle = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleReportedEvent($updatedArticle));

        return $updatedArticle;
    }

    /**
     * Clear article reports
     */
    public function clearArticleReports(int $id): Article
    {
        $this->articleRepository->update($id, [
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
        ]);

        $article = $this->articleManagementService->getArticleWithRelationships($id);

        Event::dispatch(new ArticleReportsClearedEvent($article));

        return $article;
    }
}
