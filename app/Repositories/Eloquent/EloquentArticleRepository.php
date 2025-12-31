<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Article;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of ArticleRepositoryInterface
 *
 * @extends BaseEloquentRepository<Article>
 */
final class EloquentArticleRepository extends BaseEloquentRepository implements ArticleRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Article>
     */
    protected function getModelClass(): string
    {
        return Article::class;
    }

    /**
     * Create a new article
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Article
    {
        /** @var Article $article */
        $article = parent::create($data);

        return $article;
    }

    /**
     * Find an article by ID
     */
    public function findById(int $id): ?Article
    {
        /** @var Article|null $article */
        $article = parent::findById($id);

        return $article;
    }

    /**
     * Find an article by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Article
    {
        /** @var Article $article */
        $article = parent::findOrFail($id);

        return $article;
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
     * Get a query builder instance
     *
     * @return Builder<Article>
     */
    public function query(): Builder
    {
        /** @var Builder<Article> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get articles with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Article>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Article> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }
}
