<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\Article;

interface ArticleFeatureServiceInterface
{
    /**
     * Feature an article (using route model binding)
     */
    public function featureArticle(Article $article): Article;

    /**
     * Unfeature an article
     */
    public function unfeatureArticle(int $id): Article;

    /**
     * Pin an article (using route model binding)
     */
    public function pinArticle(Article $article): Article;

    /**
     * Unpin an article (using route model binding)
     */
    public function unpinArticle(Article $article): Article;
}
