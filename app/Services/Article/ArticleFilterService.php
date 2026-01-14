<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Data\FilterArticleDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service for filtering articles with reusable filter logic.
 *
 * This class extracts filter logic from ArticleService to improve
 * separation of concerns and testability.
 */
final class ArticleFilterService
{
    public function __construct(
        private readonly ArticleQueryBuilder $queryBuilder
    ) {}

    /**
     * Get articles with filters and pagination.
     *
     * @return LengthAwarePaginator<int, \App\Models\Article>
     */
    public function getFilteredArticles(FilterArticleDTO $dto): LengthAwarePaginator
    {
        $query = $this->queryBuilder->baseQuery();

        // Apply filters
        $this->queryBuilder->applyFilters($query, $dto);

        // Apply sorting
        $this->queryBuilder->applySorting($query, $dto->sortBy, $dto->sortDirection);

        // Apply pagination
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
