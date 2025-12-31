<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent implementation of UserRepositoryInterface
 */
final class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update an existing user
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $user = $this->findOrFail($id);

        return $user->update($data);
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find a user by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Delete a user
     */
    public function delete(int $id): bool
    {
        $user = $this->findOrFail($id);

        /** @var bool $result */
        $result = $user->delete();

        return $result;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query();
    }

    /**
     * Get users with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $this->query()->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }

    /**
     * Get all users
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::all();
    }
}
