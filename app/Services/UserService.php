<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

final class UserService
{
    /**
     * Get users with filters and pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsers(array $params): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments']);

        // Apply filters
        $this->applyFilters($query, $params);

        // Apply sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';
        $query->orderBy((string) $sortBy, (string) $sortDirection);

        // Apply pagination
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;

        return $query->paginate((int) $perPage, ['*'], 'page', (int) $page);
    }

    /**
     * Get a single user by ID
     */
    public function getUserById(int $id): User
    {
        return User::query()
            ->with(['roles:id,name,slug'])
            ->withCount(['articles', 'comments'])
            ->findOrFail($id);
    }

    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make((string) $data['password']),
            'avatar_url' => $data['avatar_url'] ?? null,
            'bio' => $data['bio'] ?? null,
            'twitter' => $data['twitter'] ?? null,
            'facebook' => $data['facebook'] ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'github' => $data['github'] ?? null,
            'website' => $data['website'] ?? null,
            'banned_at' => $data['banned_at'] ?? null,
            'blocked_at' => $data['blocked_at'] ?? null,
        ]);

        // Assign default role if specified
        if (isset($data['role_id'])) {
            /** @var Role $role */
            $role = Role::findOrFail($data['role_id']);
            $user->roles()->attach($role->id);
        }

        return $user->load(['roles:id,name,slug']);
    }

    /**
     * Update an existing user
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);

        $updateData = [
            'name' => array_key_exists('name', $data) ? $data['name'] : $user->name,
            'email' => array_key_exists('email', $data) ? $data['email'] : $user->email,
            'avatar_url' => array_key_exists('avatar_url', $data) ? $data['avatar_url'] : $user->avatar_url,
            'bio' => array_key_exists('bio', $data) ? $data['bio'] : $user->bio,
            'twitter' => array_key_exists('twitter', $data) ? $data['twitter'] : $user->twitter,
            'facebook' => array_key_exists('facebook', $data) ? $data['facebook'] : $user->facebook,
            'linkedin' => array_key_exists('linkedin', $data) ? $data['linkedin'] : $user->linkedin,
            'github' => array_key_exists('github', $data) ? $data['github'] : $user->github,
            'website' => array_key_exists('website', $data) ? $data['website'] : $user->website,
            'banned_at' => array_key_exists('banned_at', $data) ? $data['banned_at'] : $user->banned_at,
            'blocked_at' => array_key_exists('blocked_at', $data) ? $data['blocked_at'] : $user->blocked_at,
        ];

        // Update password if provided
        if (isset($data['password'])) {
            $updateData['password'] = Hash::make((string) $data['password']);
        }

        $user->update($updateData);

        // Update roles if specified
        if (isset($data['role_ids'])) {
            /** @var array<int> $roleIds */
            $roleIds = $data['role_ids'];
            $user->roles()->sync($roleIds);
        }

        return $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);
    }

    /**
     * Delete a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteUser(int $id): bool
    {
        $this->preventSelfAction($id, 'cannot_delete_self');

        $user = User::findOrFail($id);

        /** @var bool $deleted */
        $deleted = $user->delete();

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

        $user = User::findOrFail($id);
        $user->update(['banned_at' => now()]);

        return $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);
    }

    /**
     * Unban a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbanUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_unban_self');

        $user = User::findOrFail($id);
        $user->update(['banned_at' => null]);

        return $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);
    }

    /**
     * Block a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function blockUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_block_self');

        $user = User::findOrFail($id);
        $user->update(['blocked_at' => now()]);

        return $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);
    }

    /**
     * Unblock a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unblockUser(int $id): User
    {
        $this->preventSelfAction($id, 'cannot_unblock_self');

        $user = User::findOrFail($id);
        $user->update(['blocked_at' => null]);

        return $user->load(['roles:id,name,slug'])->loadCount(['articles', 'comments']);
    }

    /**
     * Get all roles
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::query()->with(['permissions:id,name,slug'])->get();
    }

    /**
     * Get all permissions
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Permission>
     */
    public function getAllPermissions(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::query()->get();
    }

    /**
     * Assign roles to user
     *
     * @param  array<int>  $roleIds
     */
    public function assignRoles(int $userId, array $roleIds): User
    {
        $user = User::findOrFail($userId);
        $user->roles()->sync($roleIds);

        return $user->load(['roles:id,name,slug']);
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
     * @param  array<string, mixed>  $params
     */
    private function applyFilters(Builder $query, array $params): void
    {
        // Search in name and email
        if (! empty($params['search'])) {
            /** @var mixed $searchParam */
            $searchParam = $params['search'];
            $searchTerm = (string) $searchParam;
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by role
        if (! empty($params['role_id'])) {
            $query->whereHas('roles', function (Builder $q) use ($params) {
                $q->where('roles.id', (int) $params['role_id']);
            });
        }

        // Filter by status
        if (! empty($params['status'])) {
            switch ($params['status']) {
                case 'banned':
                    $query->whereNotNull('banned_at');
                    break;
                case 'blocked':
                    $query->whereNotNull('blocked_at');
                    break;
                case 'active':
                    $query->whereNull('banned_at')->whereNull('blocked_at');
                    break;
            }
        }

        // Filter by date range
        if (! empty($params['created_after'])) {
            $query->where('created_at', '>=', $params['created_after']);
        }

        if (! empty($params['created_before'])) {
            $query->where('created_at', '<=', $params['created_before']);
        }
    }
}
