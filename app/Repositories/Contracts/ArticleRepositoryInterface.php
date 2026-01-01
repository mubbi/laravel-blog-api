<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Article repository interface
 */
interface ArticleRepositoryInterface
{
    /**
     * Create a new article
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Article;

    /**
     * Update an existing article
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find an article by ID
     */
    public function findById(int $id): ?Article;

    /**
     * Find an article by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Article;

    /**
     * Find an article by slug
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * Find an article by slug or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findBySlugOrFail(string $slug): Article;

    /**
     * Delete an article
     */
    public function delete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<Article>
     */
    public function query(): Builder;

    /**
     * Get articles with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Article>
     */
    public function paginate(array $params): LengthAwarePaginator;
}
