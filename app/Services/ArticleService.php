<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FilterArticleDTO;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ArticleService
{
    public function __construct(
        private readonly \App\Repositories\Contracts\ArticleRepositoryInterface $articleRepository,
        private readonly \App\Repositories\Contracts\CategoryRepositoryInterface $categoryRepository,
        private readonly \App\Repositories\Contracts\TagRepositoryInterface $tagRepository,
        private readonly \App\Repositories\Contracts\CommentRepositoryInterface $commentRepository,
    ) {}

    /**
     * Get articles with filters and pagination
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleDTO $dto): LengthAwarePaginator
    {
        $query = $this->articleRepository->query()
            ->with([
                'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                'approver:id,name,email,avatar_url',
                'updater:id,name,email,avatar_url',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'authors:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
            ])
            ->withCount('comments');

        // Apply filters
        $this->applyFilters($query, $dto);

        // Apply sorting
        $query->orderBy($dto->sortBy, $dto->sortDirection);

        // Apply pagination
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get a single article by slug
     */
    public function getArticleBySlug(string $slug): Article
    {
        return $this->articleRepository->query()
            ->with([
                'author:id,name,email,avatar_url,bio,twitter,facebook,linkedin,github,website',
                'approver:id,name,email,avatar_url',
                'updater:id,name,email,avatar_url',
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
     */
    private function applyFilters(Builder $query, FilterArticleDTO $dto): void
    {
        // Search in title, subtitle, excerpt, and content
        if ($dto->search !== null) {
            $query->where(function (Builder $q) use ($dto) {
                $q->where('title', 'like', "%{$dto->search}%")
                    ->orWhere('subtitle', 'like', "%{$dto->search}%")
                    ->orWhere('excerpt', 'like', "%{$dto->search}%")
                    ->orWhere('content_markdown', 'like', "%{$dto->search}%");
            });
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
            $query->whereHas('authors', function (Builder $q) use ($dto) {
                $q->where('user_id', $dto->authorId);
            });
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
        return $this->categoryRepository->all(['id', 'name', 'slug']);
    }

    /**
     * Get all tags
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Tag>
     */
    public function getAllTags()
    {
        return $this->tagRepository->all(['id', 'name', 'slug']);
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
        $query = $this->commentRepository->query()
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
        $replies = $this->commentRepository->query()
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
