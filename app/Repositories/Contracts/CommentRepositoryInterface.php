<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Comment repository interface
 */
interface CommentRepositoryInterface
{
    /**
     * Create a new comment
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Comment;

    /**
     * Update an existing comment
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a comment by ID
     */
    public function findById(int $id): ?Comment;

    /**
     * Find a comment by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Comment;

    /**
     * Delete a comment (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Force delete a comment
     */
    public function forceDelete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<Comment>
     */
    public function query(): Builder;

    /**
     * Get comments with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(array $params): LengthAwarePaginator;
}
