<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of CommentRepositoryInterface
 *
 * @extends BaseEloquentRepository<Comment>
 */
final class EloquentCommentRepository extends BaseEloquentRepository implements CommentRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<Comment>
     */
    protected function getModelClass(): string
    {
        return Comment::class;
    }

    /**
     * Create a new comment
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Comment
    {
        /** @var Comment $comment */
        $comment = parent::create($data);

        return $comment;
    }

    /**
     * Find a comment by ID
     */
    public function findById(int $id): ?Comment
    {
        /** @var Comment|null $comment */
        $comment = parent::findById($id);

        return $comment;
    }

    /**
     * Find a comment by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Comment
    {
        /** @var Comment $comment */
        $comment = parent::findOrFail($id);

        return $comment;
    }

    /**
     * Force delete a comment
     */
    public function forceDelete(int $id): bool
    {
        $comment = $this->findOrFail($id);

        /** @var bool $result */
        $result = $comment->forceDelete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<Comment>
     */
    public function query(): Builder
    {
        /** @var Builder<Comment> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get comments with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, Comment> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }
}
