<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\CacheKeys;
use App\Data\User\CreateUserDTO;
use App\Data\User\FilterUserDTO;
use App\Data\User\FilterUserFollowersDTO;
use App\Data\User\UpdateUserDTO;
use App\Events\User\UserBannedEvent;
use App\Events\User\UserBlockedEvent;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserDeletedEvent;
use App\Events\User\UserUnbannedEvent;
use App\Events\User\UserUnblockedEvent;
use App\Events\User\UserUpdatedEvent;
use App\Models\User;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

final class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository
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
     * Get user with relationships loaded (for route model binding)
     */
    public function getUserWithRelationships(User $user): User
    {
        $user->load(['roles:id,name,slug']);
        $user->loadCount(['articles', 'comments']);

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
        $user = DB::transaction(function () use ($dto) {
            $userData = $dto->toArray();
            $userData['password'] = Hash::make($dto->password);

            $user = $this->userRepository->create($userData);

            // Assign default role if specified
            if ($dto->roleId !== null) {
                $role = $this->roleRepository->findOrFail($dto->roleId);
                $user->roles()->attach($role->id);
            }

            $user->load(['roles:id,name,slug']);

            return $user;
        });

        Event::dispatch(new UserCreatedEvent($user));

        return $user;
    }

    /**
     * Update an existing user (using route model binding)
     */
    public function updateUser(User $user, UpdateUserDTO $dto): User
    {
        $freshUser = DB::transaction(function () use ($user, $dto) {
            $updateData = $dto->toArray();

            // Update password if provided
            if ($dto->password !== null) {
                $updateData['password'] = Hash::make($dto->password);
            }

            $this->userRepository->update($user->id, $updateData);

            // Update roles if specified
            if ($dto->roleIds !== null) {
                $user->roles()->sync($dto->roleIds);
            }

            /** @var User $freshUser */
            $freshUser = $user->fresh(['roles:id,name,slug']);
            $freshUser->loadCount(['articles', 'comments']);

            return $freshUser;
        });

        Event::dispatch(new UserUpdatedEvent($freshUser));

        return $freshUser;
    }

    /**
     * Delete a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteUser(User $user, User $currentUser): bool
    {
        $this->preventSelfAction($user->id, $currentUser->id, 'cannot_delete_self');

        $email = $user->email;
        $userId = $user->id;
        $deleted = $this->userRepository->delete($user->id);

        if ($deleted) {
            Event::dispatch(new UserDeletedEvent($userId, $email));
        }

        return $deleted;
    }

    /**
     * Ban a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function banUser(User $user, User $currentUser): User
    {
        $this->preventSelfAction($user->id, $currentUser->id, 'cannot_ban_self');

        $this->userRepository->update($user->id, ['banned_at' => now()]);
        $user->refresh();
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserBannedEvent($user));

        return $user;
    }

    /**
     * Unban a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbanUser(User $user, User $currentUser): User
    {
        $this->preventSelfAction($user->id, $currentUser->id, 'cannot_unban_self');

        $this->userRepository->update($user->id, ['banned_at' => null]);
        $user->refresh();
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserUnbannedEvent($user));

        return $user;
    }

    /**
     * Block a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function blockUser(User $user, User $currentUser): User
    {
        $this->preventSelfAction($user->id, $currentUser->id, 'cannot_block_self');

        $this->userRepository->update($user->id, ['blocked_at' => now()]);
        $user->refresh();
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserBlockedEvent($user));

        return $user;
    }

    /**
     * Unblock a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unblockUser(User $user, User $currentUser): User
    {
        $this->preventSelfAction($user->id, $currentUser->id, 'cannot_unblock_self');

        $this->userRepository->update($user->id, ['blocked_at' => null]);
        $user->refresh();
        $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);

        Event::dispatch(new UserUnblockedEvent($user));

        return $user;
    }

    /**
     * Get all roles with cached permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role>
     */
    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $result */
        $result = Cache::remember(CacheKeys::ALL_ROLES_CACHE_KEY, CacheKeys::CACHE_TTL, function () {
            return $this->roleRepository->getAllWithPermissions();
        });

        return $result;
    }

    /**
     * Get all permissions with caching
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission>
     */
    public function getAllPermissions(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $result */
        $result = Cache::remember(CacheKeys::ALL_PERMISSIONS_CACHE_KEY, CacheKeys::CACHE_TTL, function () {
            return $this->permissionRepository->getAll();
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
     * Prevent users from performing actions on themselves
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function preventSelfAction(int $id, int $currentUserId, string $errorKey): void
    {
        if ($id === $currentUserId) {
            throw new AuthorizationException(__("common.{$errorKey}"));
        }
    }

    /**
     * Follow a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function followUser(User $userToFollow, User $currentUser): bool
    {
        $this->preventSelfAction($userToFollow->id, $currentUser->id, 'cannot_follow_self');

        // Check if already following
        if ($currentUser->following()->where('following_id', $userToFollow->id)->exists()) {
            return false;
        }

        $currentUser->following()->attach($userToFollow->id);

        Event::dispatch(new \App\Events\User\UserFollowedEvent($currentUser, $userToFollow));

        return true;
    }

    /**
     * Unfollow a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfollowUser(User $userToUnfollow, User $currentUser): bool
    {
        $this->preventSelfAction($userToUnfollow->id, $currentUser->id, 'cannot_unfollow_self');

        // Check if not following
        if (! $currentUser->following()->where('following_id', $userToUnfollow->id)->exists()) {
            return false;
        }

        $currentUser->following()->detach($userToUnfollow->id);

        Event::dispatch(new \App\Events\User\UserUnfollowedEvent($currentUser, $userToUnfollow));

        return true;
    }

    /**
     * Get followers of a user with pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getFollowers(User $user, FilterUserFollowersDTO $dto): LengthAwarePaginator
    {
        $query = $user->followers()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments']);

        // Apply sorting - use users table columns
        $sortColumn = match ($dto->sortBy) {
            'name' => 'users.name',
            'created_at' => 'users.created_at',
            'updated_at' => 'users.updated_at',
            default => 'users.created_at',
        };

        $query->orderBy($sortColumn, $dto->sortDirection);

        // Apply pagination
        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = $query->paginate($dto->perPage, ['*'], 'page', $dto->page);

        return $paginator;
    }

    /**
     * Get users that a user is following with pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getFollowing(User $user, FilterUserFollowersDTO $dto): LengthAwarePaginator
    {
        $query = $user->following()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments']);

        // Apply sorting - use users table columns
        $sortColumn = match ($dto->sortBy) {
            'name' => 'users.name',
            'created_at' => 'users.created_at',
            'updated_at' => 'users.updated_at',
            default => 'users.created_at',
        };

        $query->orderBy($sortColumn, $dto->sortDirection);

        // Apply pagination
        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = $query->paginate($dto->perPage, ['*'], 'page', $dto->page);

        return $paginator;
    }

    /**
     * Get user profile with relationships
     */
    public function getUserProfile(User $user): User
    {
        $user->load([
            'roles:id,name,slug',
            'roles.permissions:id,name,slug',
        ]);
        $user->loadCount([
            'articles',
            'comments',
            'followers',
            'following',
        ]);

        // Pre-warm cache for this user
        $user->getCachedRoles();
        $user->getCachedPermissions();

        return $user;
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
