<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User repository interface
 */
interface UserRepositoryInterface
{
    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User;

    /**
     * Delete a user
     */
    public function delete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<User>
     */
    public function query(): Builder;

    /**
     * Get users with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $params): LengthAwarePaginator;

    /**
     * Get all users
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, User>
     */
    public function all(?array $columns = null): Collection;
}
