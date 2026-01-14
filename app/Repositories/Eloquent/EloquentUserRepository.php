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
 *
 * @extends BaseEloquentRepository<User>
 */
final class EloquentUserRepository extends BaseEloquentRepository implements UserRepositoryInterface
{
    /**
     * Get the model class name
     *
     * @return class-string<User>
     */
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        /** @var User $user */
        $user = parent::create($data);

        return $user;
    }

    /**
     * Find a user by ID
     */
    public function findById(int $id): ?User
    {
        /** @var User|null $user */
        $user = parent::findById($id);

        return $user;
    }

    /**
     * Find a user by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): User
    {
        /** @var User $user */
        $user = parent::findOrFail($id);

        return $user;
    }

    /**
     * Get a query builder instance
     *
     * @return Builder<User>
     */
    public function query(): Builder
    {
        /** @var Builder<User> $builder */
        $builder = parent::query();

        return $builder;
    }

    /**
     * Get users with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(array $params): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = parent::paginate($params);

        return $paginator;
    }

    /**
     * Get all users
     *
     * @param  array<string>|null  $columns
     * @return Collection<int, User>
     */
    public function all(?array $columns = null): Collection
    {
        /** @var Collection<int, User> $collection */
        $collection = parent::all($columns);

        return $collection;
    }
}
