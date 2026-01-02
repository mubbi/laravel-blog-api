<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CreateArticleDTO;
use App\Data\FilterArticleManagementDTO;
use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ArticleManagementService
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository
    ) {}

    /**
     * Get articles with filters and pagination for article management
     * Non-admin users will only see their own articles
     *
     * @param  int|null  $userIdForFiltering  If provided, filter articles to only those created by this user
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleManagementDTO $dto, ?int $userIdForFiltering = null): LengthAwarePaginator
    {
        $query = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->withCount(['comments', 'authors']);

        // Filter by user if provided (non-admin users see only their own articles)
        if ($userIdForFiltering !== null) {
            $query->where('created_by', $userIdForFiltering);
        }

        // Apply filters
        $this->applyFilters($query, $dto);

        // Apply sorting
        $query->orderBy($dto->sortBy, $dto->sortDirection);

        // Apply pagination
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get a single article by ID for article management
     * Non-admin users can only access their own articles (authorization should be checked in request/controller)
     */
    public function getArticleById(int $id): Article
    {
        return $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug', 'comments.user:id,name,email'])
            ->withCount(['comments', 'authors'])
            ->findOrFail($id);
    }

    /**
     * Load article relationships for management views
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    private function loadArticleRelationships(Builder $query): Builder
    {
        return $query->with([
            'author:id,name,email',
            'approver:id,name,email',
            'updater:id,name,email',
            'categories:id,name,slug',
            'tags:id,name,slug',
        ]);
    }

    /**
     * Get article with relationships loaded
     * Made public for use by other services
     */
    public function getArticleWithRelationships(int $id): Article
    {
        return $this->loadArticleRelationships($this->articleRepository->query())
            ->findOrFail($id);
    }

    /**
     * Load relationships on an existing article model (using route model binding)
     * Made public for use by other services
     */
    public function loadArticleRelationshipsOnModel(Article $article): Article
    {
        $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
        $article->loadCount(['comments', 'authors']);

        return $article;
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<Article>  $query
     */
    private function applyFilters(Builder $query, FilterArticleManagementDTO $dto): void
    {
        // Search in title and content
        if ($dto->search !== null) {
            $query->where(function (Builder $q) use ($dto) {
                $q->where('title', 'like', "%{$dto->search}%")
                    ->orWhere('content_markdown', 'like', "%{$dto->search}%")
                    ->orWhere('excerpt', 'like', "%{$dto->search}%");
            });
        }

        // Filter by status
        if ($dto->status !== null) {
            $query->where('status', $dto->status->value);
        }

        // Filter by author
        if ($dto->authorId !== null) {
            $query->where('created_by', $dto->authorId);
        }

        // Filter by category
        if ($dto->categoryId !== null) {
            $query->whereHas('categories', function (Builder $q) use ($dto) {
                $q->where('categories.id', $dto->categoryId);
            });
        }

        // Filter by tag
        if ($dto->tagId !== null) {
            $query->whereHas('tags', function (Builder $q) use ($dto) {
                $q->where('tags.id', $dto->tagId);
            });
        }

        // Filter by featured status
        if ($dto->isFeatured !== null) {
            $query->where('is_featured', $dto->isFeatured);
        }

        // Filter by pinned status
        if ($dto->isPinned !== null) {
            $query->where('is_pinned', $dto->isPinned);
        }

        // Filter by reported articles
        if ($dto->hasReports !== null) {
            if ($dto->hasReports) {
                $query->where('report_count', '>', 0);
            } else {
                $query->where('report_count', 0);
            }
        }

        // Filter by date range
        if ($dto->createdAfter !== null) {
            $query->where('created_at', '>=', $dto->createdAfter);
        }

        if ($dto->createdBefore !== null) {
            $query->where('created_at', '<=', $dto->createdBefore);
        }

        if ($dto->publishedAfter !== null) {
            $query->where('published_at', '>=', $dto->publishedAfter);
        }

        if ($dto->publishedBefore !== null) {
            $query->where('published_at', '<=', $dto->publishedBefore);
        }
    }

    /**
     * Create a new article with relationships
     */
    public function createArticle(CreateArticleDTO $dto): Article
    {
        return DB::transaction(function () use ($dto) {
            // Create the article
            $article = $this->articleRepository->create($dto->toArray());

            // Attach categories
            if (! empty($dto->categoryIds)) {
                $article->categories()->attach($dto->categoryIds);
            }

            // Attach tags
            if (! empty($dto->tagIds)) {
                $article->tags()->attach($dto->tagIds);
            }

            // Attach authors with roles (DTO always provides at least the creator as author)
            foreach ($dto->authors as $author) {
                $article->authors()->attach($author['user_id'], ['role' => $author['role']]);
            }

            // Reload with relationships
            return $this->getArticleWithRelationships($article->id);
        });
    }
}
