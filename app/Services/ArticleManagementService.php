<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ArticleManagementService
{
    /**
     * Get articles with filters and pagination for admin management
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(array $params): LengthAwarePaginator
    {
        $query = Article::query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->withCount(['comments', 'authors']);

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';
        $query->orderBy((string) $sortBy, (string) $sortDirection);

        // Apply pagination
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $query->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }

    /**
     * Get a single article by ID for admin management
     */
    public function getArticleById(int $id): Article
    {
        return Article::query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug', 'comments.user:id,name,email'])
            ->withCount(['comments', 'authors'])
            ->findOrFail($id);
    }

    /**
     * Approve an article
     */
    public function approveArticle(int $id, int $approvedBy): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::PUBLISHED,
            'approved_by' => $approvedBy,
            'published_at' => now(),
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Reject an article (set to draft)
     */
    public function rejectArticle(int $id, int $rejectedBy): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::DRAFT,
            'approved_by' => $rejectedBy,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Feature an article
     */
    public function featureArticle(int $id): Article
    {
        try {
            $article = Article::findOrFail($id);
            $newFeaturedStatus = ! $article->is_featured;
            $article->update([
                'is_featured' => $newFeaturedStatus,
                'featured_at' => $newFeaturedStatus ? now() : null,
            ]);

            /** @var Article $freshArticle */
            $freshArticle = $article->fresh();

            return $freshArticle;
        } catch (\Throwable $e) {
            \Log::error('FeatureArticle error', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Unfeature an article
     */
    public function unfeatureArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'is_featured' => false,
            'featured_at' => null,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Pin an article
     */
    public function pinArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Unpin an article
     */
    public function unpinArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Archive an article
     */
    public function archiveArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::ARCHIVED,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Restore an article from archive
     */
    public function restoreArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Trash an article
     */
    public function trashArticle(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::TRASHED,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Restore an article from trash
     */
    public function restoreFromTrash(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'status' => ArticleStatus::DRAFT,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Permanently delete an article
     */
    public function deleteArticle(int $id): bool
    {
        $article = Article::findOrFail($id);

        /** @var bool $deleted */
        $deleted = $article->delete();

        return $deleted;
    }

    /**
     * Report an article
     */
    public function reportArticle(int $id, string $reason): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'report_count' => $article->report_count + 1,
            'last_reported_at' => now(),
            'report_reason' => $reason,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Clear article reports
     */
    public function clearArticleReports(int $id): Article
    {
        $article = Article::findOrFail($id);
        $article->update([
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
        ]);

        return $article->load(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<Article>  $query
     * @param  array<string, mixed>  $params
     */
    private function applyFilters(Builder $query, array $params): void
    {
        // Search in title and content
        if (! empty($params['search'])) {
            /** @var mixed $searchParam */
            $searchParam = $params['search'];
            $searchTerm = (string) $searchParam;
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content_markdown', 'like', "%{$searchTerm}%")
                    ->orWhere('excerpt', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Filter by author
        if (! empty($params['author_id'])) {
            $query->where('created_by', (int) $params['author_id']);
        }

        // Filter by category
        if (! empty($params['category_id'])) {
            $query->whereHas('categories', function (Builder $q) use ($params) {
                $q->where('categories.id', (int) $params['category_id']);
            });
        }

        // Filter by tag
        if (! empty($params['tag_id'])) {
            $query->whereHas('tags', function (Builder $q) use ($params) {
                $q->where('tags.id', (int) $params['tag_id']);
            });
        }

        // Filter by featured status
        if (isset($params['is_featured'])) {
            $query->where('is_featured', (bool) $params['is_featured']);
        }

        // Filter by pinned status
        if (isset($params['is_pinned'])) {
            $query->where('is_pinned', (bool) $params['is_pinned']);
        }

        // Filter by reported articles
        if (isset($params['has_reports'])) {
            if ((bool) $params['has_reports']) {
                $query->where('report_count', '>', 0);
            } else {
                $query->where('report_count', 0);
            }
        }

        // Filter by date range
        if (! empty($params['created_after'])) {
            $query->where('created_at', '>=', $params['created_after']);
        }

        if (! empty($params['created_before'])) {
            $query->where('created_at', '<=', $params['created_before']);
        }

        if (! empty($params['published_after'])) {
            $query->where('published_at', '>=', $params['published_after']);
        }

        if (! empty($params['published_before'])) {
            $query->where('published_at', '<=', $params['published_before']);
        }
    }
}
