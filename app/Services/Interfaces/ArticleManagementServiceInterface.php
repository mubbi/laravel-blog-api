<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Article\CreateArticleDTO;
use App\Data\Article\FilterArticleManagementDTO;
use App\Models\Article;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArticleManagementServiceInterface
{
    /**
     * Get articles with filters and pagination for article management
     * Non-admin users will only see their own articles
     *
     * @param  int|null  $userIdForFiltering  If provided, filter articles to only those created by this user
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleManagementDTO $dto, ?int $userIdForFiltering = null): LengthAwarePaginator;

    /**
     * Get a single article by ID for article management
     * Non-admin users can only access their own articles (authorization should be checked in request/controller)
     */
    public function getArticleById(int $id): Article;

    /**
     * Get article with relationships loaded
     * Made public for use by other services
     */
    public function getArticleWithRelationships(int $id): Article;

    /**
     * Load relationships on an existing article model (using route model binding)
     * Made public for use by other services
     */
    public function loadArticleRelationshipsOnModel(Article $article): Article;

    /**
     * Create a new article with relationships
     */
    public function createArticle(CreateArticleDTO $dto): Article;
}
