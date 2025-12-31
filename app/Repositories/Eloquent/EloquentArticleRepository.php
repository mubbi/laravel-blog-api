<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of ArticleRepositoryInterface
 */
final class EloquentArticleRepository implements ArticleRepositoryInterface
{
    /**
     * Create a new article
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Article
    {
        return Article::create($data);
    }

    /**
     * Update an existing article
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $article = $this->findOrFail($id);

        return $article->update($data);
    }

    /**
     * Find an article by ID
     */
    public function findById(int $id): ?Article
    {
        return Article::find($id);
    }

    /**
     * Find an article by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Article
    {
        return Article::findOrFail($id);
    }

    /**
     * Find an article by slug
     */
    public function findBySlug(string $slug): ?Article
    {
        return Article::where('slug', $slug)->first();
    }

    /**
     * Find an article by slug or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findBySlugOrFail(string $slug): Article
    {
        return Article::where('slug', $slug)->firstOrFail();
    }

    /**
     * Delete an article
     */
    public function delete(int $id): bool
    {
        $article = $this->findOrFail($id);

        /** @var bool $result */
        $result = $article->delete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Article>
     */
    public function query(): Builder
    {
        return Article::query();
    }

    /**
     * Get articles with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Article>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }
}
