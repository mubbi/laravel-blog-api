<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
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
            ->with([
                'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'authors:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
            ])
            ->withCount('comments');

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'published_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';
        $query->orderBy((string) $sortBy, (string) $sortDirection);

        // Apply pagination
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $query->paginate((int) $perPage, ['*'], 'page', (int) $page);
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
            /** @var mixed $searchParam */
            $searchParam = $params['search'];
            $searchTerm = (string) $searchParam;
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('subtitle', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%")
                    ->orWhere('content_markdown', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if (! empty($params['status'])) {
            $query->where('status', (string) $params['status']);
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
                $q->where('user_id', (int) $params['author_id']);
            });
        }

        // Filter by creator
        if (! empty($params['created_by'])) {
            $query->where('created_by', (int) $params['created_by']);
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

    /**
     * Get paginated comments for an article (with 1 child level or for a parent comment).
     *
     * Loads the comment's user, count of replies, and top replies (limited by $repliesPerPage).
     *
     * @param  int  $articleId  The ID of the article.
     * @param  int|null  $parentId  The ID of the parent comment (if loading child comments).
     * @param  int  $perPage  Number of parent comments per page.
     * @param  int  $page  Current page number.
     * @param  int  $repliesPerPage  Number of child comments per parent.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Comment>
     */
    public function getArticleComments(
        int $articleId,
        ?int $parentId = null,
        int $perPage = 10,
        int $page = 1,
        int $repliesPerPage = 3
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        $query = Comment::query()
            ->where('article_id', $articleId)
            ->when($parentId !== null, fn ($q) => $q->where('parent_comment_id', $parentId))
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_comment_id'))
            ->orderBy('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments */
        $comments = $paginator->getCollection();

        $comments->load(['user']);
        $comments->loadCount('replies');

        // Collect IDs of parent comments
        $parentCommentIds = $comments->pluck('id');

        // Fetch replies in batch (LIMIT repliesPerPage per parent)
        $replies = Comment::query()
            ->whereIn('parent_comment_id', $parentCommentIds)
            ->with('user')
            ->withCount('replies')
            ->orderBy('created_at')
            ->get()
            ->groupBy('parent_comment_id');

        // Attach limited replies to each comment
        $comments->each(function (Comment $comment) use ($replies, $repliesPerPage) {
            $replyCollection = $replies[$comment->id] ?? collect();
            $limitedReplies = $replyCollection->take($repliesPerPage);
            $comment->setRelation('replies_page', $limitedReplies);
        });

        // Replace the collection on paginator so it's returned with relations loaded
        return $paginator->setCollection($comments);
    }
}
