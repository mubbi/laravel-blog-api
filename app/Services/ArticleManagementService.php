<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FilterArticleManagementDTO;
use App\Data\ReportArticleDTO;
use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ArticleManagementService
{
    public function __construct(
        private readonly \App\Repositories\Contracts\ArticleRepositoryInterface $articleRepository
    ) {}

    /**
     * Get articles with filters and pagination for admin management
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public function getArticles(FilterArticleManagementDTO $dto): LengthAwarePaginator
    {
        $query = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->withCount(['comments', 'authors']);

        // Apply filters
        $this->applyFilters($query, $dto);

        // Apply sorting
        $query->orderBy($dto->sortBy, $dto->sortDirection);

        // Apply pagination
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get a single article by ID for admin management
     */
    public function getArticleById(int $id): Article
    {
        return $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug', 'comments.user:id,name,email'])
            ->withCount(['comments', 'authors'])
            ->findOrFail($id);
    }

    /**
     * Approve an article
     */
    public function approveArticle(int $id, int $approvedBy): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::PUBLISHED,
            'approved_by' => $approvedBy,
            'published_at' => now(),
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Reject an article (set to draft)
     */
    public function rejectArticle(int $id, int $rejectedBy): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::DRAFT,
            'approved_by' => $rejectedBy,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Feature an article
     */
    public function featureArticle(int $id): Article
    {
        try {
            $article = $this->articleRepository->findOrFail($id);
            $newFeaturedStatus = ! $article->is_featured;

            $this->articleRepository->update($id, [
                'is_featured' => $newFeaturedStatus,
                'featured_at' => $newFeaturedStatus ? now() : null,
            ]);

            /** @var Article $freshArticle */
            $freshArticle = $this->articleRepository->query()
                ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
                ->findOrFail($id);

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
        $this->articleRepository->update($id, [
            'is_featured' => false,
            'featured_at' => null,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Pin an article
     */
    public function pinArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Unpin an article
     */
    public function unpinArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'is_pinned' => false,
            'pinned_at' => null,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Archive an article
     */
    public function archiveArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::ARCHIVED,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Restore an article from archive
     */
    public function restoreArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Trash an article
     */
    public function trashArticle(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::TRASHED,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Restore an article from trash
     */
    public function restoreFromTrash(int $id): Article
    {
        $this->articleRepository->update($id, [
            'status' => ArticleStatus::DRAFT,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $article;
    }

    /**
     * Permanently delete an article
     */
    public function deleteArticle(int $id): bool
    {
        return $this->articleRepository->delete($id);
    }

    /**
     * Report an article
     */
    public function reportArticle(int $id, ReportArticleDTO $dto): Article
    {
        $article = $this->articleRepository->findOrFail($id);

        $this->articleRepository->update($id, [
            'report_count' => $article->report_count + 1,
            'last_reported_at' => now(),
            'report_reason' => $dto->getReason(),
        ]);

        $updatedArticle = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

        return $updatedArticle;
    }

    /**
     * Clear article reports
     */
    public function clearArticleReports(int $id): Article
    {
        $this->articleRepository->update($id, [
            'report_count' => 0,
            'last_reported_at' => null,
            'report_reason' => null,
        ]);

        $article = $this->articleRepository->query()
            ->with(['author:id,name,email', 'approver:id,name,email', 'updater:id,name,email', 'categories:id,name,slug', 'tags:id,name,slug'])
            ->findOrFail($id);

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
}
