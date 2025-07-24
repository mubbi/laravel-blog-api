<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ArticleService
{
    /**
     * Get articles with filters and pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(array $params): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->withCount('comments');

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $query->orderBy($params['sort_by'], $params['sort_direction']);

        // Apply pagination
        return $query->paginate($params['per_page'], ['*'], 'page', $params['page']);
    }

    /**
     * Get a single article by slug
     */
    public function getArticleBySlug(string $slug): Article
    {
        return Article::query()
            ->with([
                'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'authors:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
            ])
            ->withCount('comments')
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<Article>  $query
     * @param  array<string, mixed>  $params
     */
    private function applyFilters(Builder $query, array $params): void
    {
        // Search in title, subtitle, excerpt, and content
        if (! empty($params['search'])) {
            $searchTerm = (string) $params['search'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('subtitle', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%")
                    ->orWhere('content_markdown', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Filter by categories (support multiple categories)
        if (! empty($params['category_slug'])) {
            $categorySlugs = is_array($params['category_slug'])
                ? $params['category_slug']
                : [$params['category_slug']];

            $query->whereHas('categories', function (Builder $q) use ($categorySlugs) {
                $q->whereIn('slug', $categorySlugs);
            });
        }

        // Filter by tags (support multiple tags)
        if (! empty($params['tag_slug'])) {
            $tagSlugs = is_array($params['tag_slug'])
                ? $params['tag_slug']
                : [$params['tag_slug']];

            $query->whereHas('tags', function (Builder $q) use ($tagSlugs) {
                $q->whereIn('slug', $tagSlugs);
            });
        }

        // Filter by author (from article_authors table)
        if (! empty($params['author_id'])) {
            $query->whereHas('authors', function (Builder $q) use ($params) {
                $q->where('user_id', $params['author_id']);
            });
        }

        // Filter by creator
        if (! empty($params['created_by'])) {
            $query->where('created_by', $params['created_by']);
        }

        // Filter by publication date range
        if (! empty($params['published_after'])) {
            $query->where('published_at', '>=', $params['published_after']);
        }

        if (! empty($params['published_before'])) {
            $query->where('published_at', '<=', $params['published_before']);
        }

        // Only include published articles for public access (unless specifically querying other statuses)
        if (empty($params['status'])) {
            $query->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        }
    }

    /**
     * Get all categories
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    public function getAllCategories()
    {
        return Category::query()->get(['id', 'name', 'slug']);
    }

    /**
     * Get all tags
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tag>
     */
    public function getAllTags()
    {
        return Tag::query()->get(['id', 'name', 'slug']);
    }
}
