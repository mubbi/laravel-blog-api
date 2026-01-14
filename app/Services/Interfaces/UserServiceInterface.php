<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\CreateUserDTO;
use App\Data\FilterUserDTO;
use App\Data\FilterUserFollowersDTO;
use App\Data\UpdateUserDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    /**
     * Get users with filters and pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getUsers(FilterUserDTO $dto): LengthAwarePaginator;

    /**
     * Get a single user by ID with cached roles and permissions
     */
    public function getUserById(int $id): User;

    /**
     * Get user with relationships loaded (for route model binding)
     */
    public function getUserWithRelationships(User $user): User;

    /**
     * Create a new user
     */
    public function createUser(CreateUserDTO $dto): User;

    /**
     * Update an existing user (using route model binding)
     */
    public function updateUser(User $user, UpdateUserDTO $dto): User;

    /**
     * Delete a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteUser(User $user, User $currentUser): bool;

    /**
     * Ban a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function banUser(User $user, User $currentUser): User;

    /**
     * Unban a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unbanUser(User $user, User $currentUser): User;

    /**
     * Block a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function blockUser(User $user, User $currentUser): User;

    /**
     * Unblock a user (using route model binding)
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unblockUser(User $user, User $currentUser): User;

    /**
     * Get all roles with cached permissions
     *
     * @return Collection<int, \App\Models\Role>
     */
    public function getAllRoles(): Collection;

    /**
     * Get all permissions with caching
     *
     * @return Collection<int, \App\Models\Permission>
     */
    public function getAllPermissions(): Collection;

    /**
     * Assign roles to user and clear cache
     *
     * @param  array<int>  $roleIds
     */
    public function assignRoles(int $userId, array $roleIds): User;

    /**
     * Follow a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function followUser(User $userToFollow, User $currentUser): bool;

    /**
     * Unfollow a user
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function unfollowUser(User $userToUnfollow, User $currentUser): bool;

    /**
     * Get followers of a user with pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getFollowers(User $user, FilterUserFollowersDTO $dto): LengthAwarePaginator;

    /**
     * Get users that a user is following with pagination
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function getFollowing(User $user, FilterUserFollowersDTO $dto): LengthAwarePaginator;

    /**
     * Get user profile with relationships
     */
    public function getUserProfile(User $user): User;
}
