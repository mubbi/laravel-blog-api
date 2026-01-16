<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Article\FilterArticleDTO;
use App\Models\Article;
use App\Models\ArticleLike;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

interface ArticleServiceInterface
{
    /**
     * Get articles with filters and pagination
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleDTO $dto): LengthAwarePaginator;

    /**
     * Get a single article by slug
     */
    public function getArticleBySlug(string $slug): Article;

    /**
     * Get paginated comments for an article (with 1 child level or for a parent comment).
     *
     * @param  Article  $article  The article model instance.
     * @param  int|null  $parentId  The ID of the parent comment (if loading child comments).
     * @param  int  $perPage  Number of parent comments per page.
     * @param  int  $page  Current page number.
     * @param  int  $repliesPerPage  Number of child comments per parent.
     * @return Paginator<int, \App\Models\Comment>
     */
    public function getArticleComments(
        Article $article,
        ?int $parentId = null,
        int $perPage = 10,
        int $page = 1,
        int $repliesPerPage = 3
    ): Paginator;

    /**
     * Like an article
     *
     * @param  Article  $article  The article model instance
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous likes
     *
     * @throws \InvalidArgumentException
     */
    public function likeArticle(Article $article, ?int $userId = null, ?string $ipAddress = null): ArticleLike;

    /**
     * Dislike an article
     *
     * @param  Article  $article  The article model instance
     * @param  int|null  $userId  The user ID if authenticated, null for anonymous
     * @param  string|null  $ipAddress  The IP address for anonymous dislikes
     *
     * @throws \InvalidArgumentException
     */
    public function dislikeArticle(Article $article, ?int $userId = null, ?string $ipAddress = null): ArticleLike;
}
