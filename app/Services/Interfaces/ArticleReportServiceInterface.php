<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Article\ReportArticleDTO;
use App\Models\Article;

interface ArticleReportServiceInterface
{
    /**
     * Report an article (using route model binding)
     */
    public function reportArticle(Article $article, ReportArticleDTO $dto): Article;

    /**
     * Clear article reports (using route model binding)
     */
    public function clearArticleReports(Article $article): Article;
}
