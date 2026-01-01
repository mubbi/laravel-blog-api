<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Data\CreateUserDTO;
use App\Data\FilterUserDTO;
use App\Data\UpdateUserDTO;
use App\Events\User\UserBannedEvent;
use App\Events\User\UserBlockedEvent;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserDeletedEvent;
use App\Events\User\UserUnbannedEvent;
use App\Events\User\UserUnblockedEvent;
use App\Events\User\UserUpdatedEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get users with filters and pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsers(FilterUserDTO $dto): LengthAwarePaginator
    {
        $query = $this->userRepository->query()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments']);

        // Apply filters
        $this->applyFilters($query, $dto);

        // Apply sorting
        $query->orderBy($dto->sortBy, $dto->sortDirection);

        // Apply pagination
        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get a single user by ID with cached roles and permissions
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->query()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments'])
            ->findOrFail($id);

        // Pre-warm cache for this user
        $user->getCachedRoles();
        $user->getCachedPermissions();

        return $user;
    }

    /**
     * Create a new user
     */
    public function createUser(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $userData = $dto->toArray();
            $userData['password'] = Hash::make($dto->password);

            $user = $this->userRepository->create($userData);

            // Assign default role if specified
            if ($dto->roleId !== null) {
                /** @var Role $role */
                $role = Role::findOrFail($dto->roleId);
                $user->roles()->attach($role->id);
            }

            $user->load(['roles:id,name,slug']);

            Event::dispatch(new UserCreatedEvent($user));

            return $user;
        });
    }

    /**
     * Update an existing user
     */
    public function updateUser(int $id, UpdateUserDTO $dto): User
    {
        return DB::transaction(function () use ($id, $dto) {
            $user = $this->userRepository->findOrFail($id);

            $updateData = $dto->toArray();

            // Update password if provided
            if ($dto->password !== null) {
                $updateData['password'] = Hash::make($dto->password);
            }

            $this->userRepository->update($id, $updateData);

            // Update roles if specified
            if ($dto->roleIds !== null) {
                $user->roles()->sync($dto->roleIds);
            }

            /** @var User $freshUser */
            $freshUser = $user->fresh(['roles:id,name,slug']);
            $freshUser->loadCount(['articles', 'comments']);

            Event::dispatch(new UserUpdatedEvent($freshUser));

            return $freshUser;
        });
    }

    /**
     * Delete a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteUser(int $id): bool
    {
        $this->preventSelfAction($id, 'cannot_delete_self');

        $user = $this->userRepository->findOrFail($id);
        $email = $user->email;
        $deleted = $this->userRepository->delete($id);

        if ($deleted) {
            Event::dispatch(new UserDeletedEvent($id, $email));
        }

        return $deleted;
    }

    /**
     * Ban a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function banUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_ban_self');

        $this->userRepository->update($id, ['banned_at' => now()]);
        $user = $this->userRepository->findOrFail($id);
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserBannedEvent($user));

        return $user;
    }

    /**
     * Unban a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbanUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_unban_self');

        $this->userRepository->update($id, ['banned_at' => null]);
        $user = $this->userRepository->findOrFail($id);
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserUnbannedEvent($user));

        return $user;
    }

    /**
     * Block a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function blockUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_block_self');

        $this->userRepository->update($id, ['blocked_at' => now()]);
        $user = $this->userRepository->findOrFail($id);
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserBlockedEvent($user));

        return $user;
    }

    /**
     * Unblock a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unblockUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_unblock_self');

        $this->userRepository->update($id, ['blocked_at' => null]);
        $user = $this->userRepository->findOrFail($id);
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserUnblockedEvent($user));

        return $user;
    }

    /**
     * Get all roles with cached permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Role> $result */
        $result = Cache::remember(CacheKeys::ALL_ROLES_CACHE_KEY, CacheKeys::CACHE_TTL, function () {
            return Role::query()->with(['permissions:id,name,slug'])->get();
        });

        return $result;
    }

    /**
     * Get all permissions with caching
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Permission>
     */
    public function getAllPermissions(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Permission> $result */
        $result = Cache::remember(CacheKeys::ALL_PERMISSIONS_CACHE_KEY, CacheKeys::CACHE_TTL, function () {
            return Permission::query()->get();
        });

        return $result;
    }

    /**
     * Assign roles to user and clear cache
     *
     * @param  array<int>  $roleIds
     */
    public function assignRoles(int $userId, array $roleIds): User
    {
        $user = $this->userRepository->findOrFail($userId);
        $user->roles()->sync($roleIds);

        // Clear user-specific caches
        $user->clearCache();

        return $user->load(['roles:id,name,slug']);
    }

    /**
     * Get users with pre-warmed caches
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsersWithWarmedCaches(FilterUserDTO $dto): LengthAwarePaginator
    {
        $paginator = $this->getUsers($dto);

        // Pre-warm cache for users in the current page
        foreach ($paginator->items() as $user) {
            $user->getCachedRoles();
            $user->getCachedPermissions();
        }

        return $paginator;
    }

    /**
     * Increment cache version (for testing purposes)
     */
    public function incrementCacheVersion(): void
    {
        /** @var int $currentVersion */
        $currentVersion = Cache::get('user_cache_version', 1);
        Cache::put('user_cache_version', $currentVersion + 1, CacheKeys::CACHE_TTL);
    }

    /**
     * Prevent users from performing actions on themselves
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function preventSelfAction(int $id, string $errorKey): void
    {
        $currentUser = auth()->user();

        if ($currentUser && $id === $currentUser->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException(__("common.{$errorKey}"));
        }
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<User>  $query
     */
    private function applyFilters(Builder $query, FilterUserDTO $dto): void
    {
        // Search in name and email
        if ($dto->search !== null) {
            $query->where(function (Builder $q) use ($dto) {
                $q->where('name', 'like', "%{$dto->search}%")
                    ->orWhere('email', 'like', "%{$dto->search}%");
            });
        }

        // Filter by role
        if ($dto->roleId !== null) {
            $query->whereHas('roles', function (Builder $q) use ($dto) {
                $q->where('roles.id', $dto->roleId);
            });
        }

        // Filter by status
        if ($dto->status !== null) {
            match ($dto->status) {
                'banned' => $query->whereNotNull('banned_at'),
                'blocked' => $query->whereNotNull('blocked_at'),
                'active' => $query->whereNull('banned_at')->whereNull('blocked_at'),
                default => null,
            };
        }

        // Filter by date range
        if ($dto->createdAfter !== null) {
            $query->where('created_at', '>=', $dto->createdAfter);
        }

        if ($dto->createdBefore !== null) {
            $query->where('created_at', '<=', $dto->createdBefore);
        }
    }
}
