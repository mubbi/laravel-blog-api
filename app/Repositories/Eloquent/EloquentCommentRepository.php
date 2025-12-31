<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of CommentRepositoryInterface
 */
final class EloquentCommentRepository implements CommentRepositoryInterface
{
    /**
     * Create a new comment
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    /**
     * Update an existing comment
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $comment = $this->findOrFail($id);

        return $comment->update($data);
    }

    /**
     * Find a comment by ID
     */
    public function findById(int $id): ?Comment
    {
        return Comment::find($id);
    }

    /**
     * Find a comment by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Comment
    {
        return Comment::findOrFail($id);
    }

    /**
     * Delete a comment (soft delete)
     */
    public function delete(int $id): bool
    {
        $comment = $this->findOrFail($id);

        /** @var bool $result */
        $result = $comment->delete();

        return $result;
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
        return Comment::query();
    }

    /**
     * Get comments with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }
}
