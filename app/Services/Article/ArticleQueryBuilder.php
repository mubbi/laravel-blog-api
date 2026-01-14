<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Data\FilterArticleDTO;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder for Article queries with reusable scopes and filters.
 *
 * This class extracts complex query logic from ArticleService to improve
 * maintainability and testability.
 */
final class ArticleQueryBuilder
{
    /**
     * Build a base query with eager loading.
     *
     * @return Builder<Article>
     */
    public function baseQuery(): Builder
    {
        return Article::query()
            ->with([
                'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                'approver:id,name,email,avatar_url',
                'updater:id,name,email,avatar_url',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'authors:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
            ])
            ->withCount('comments');
    }

    /**
     * Apply filters from DTO to the query.
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function applyFilters(Builder $query, FilterArticleDTO $dto): Builder
    {
        // Search in title, subtitle, excerpt, and content
        if ($dto->search !== null) {
            $query->search($dto->search);
        }

        // Filter by status
        if ($dto->status !== null) {
            $query->where('status', $dto->status->value);
        }

        // Filter by categories (support multiple categories)
        if ($dto->categorySlugs !== null) {
            $query->whereHas('categories', function (Builder $q) use ($dto) {
                $q->whereIn('slug', $dto->categorySlugs);
            });
        }

        // Filter by tags (support multiple tags)
        if ($dto->tagSlugs !== null) {
            $query->whereHas('tags', function (Builder $q) use ($dto) {
                $q->whereIn('slug', $dto->tagSlugs);
            });
        }

        // Filter by author (from article_authors table)
        if ($dto->authorId !== null) {
            $query->byAuthor($dto->authorId);
        }

        // Filter by creator
        if ($dto->createdBy !== null) {
            $query->where('created_by', $dto->createdBy);
        }

        // Filter by publication date range
        if ($dto->publishedAfter !== null) {
            $query->where('published_at', '>=', $dto->publishedAfter);
        }

        if ($dto->publishedBefore !== null) {
            $query->where('published_at', '<=', $dto->publishedBefore);
        }

        // Only include published articles for public access (unless specifically querying other statuses)
        if ($dto->status === null) {
            $query->published();
        }

        return $query;
    }

    /**
     * Apply sorting to the query.
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function applySorting(Builder $query, string $sortBy, string $sortDirection): Builder
    {
        return $query->orderBy($sortBy, $sortDirection);
    }
}
