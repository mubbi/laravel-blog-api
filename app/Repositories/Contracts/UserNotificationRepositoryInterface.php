<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User notification repository interface
 */
interface UserNotificationRepositoryInterface
{
    /**
     * Create a new user notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): UserNotification;

    /**
     * Update an existing user notification
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Find a user notification by ID
     */
    public function findById(int $id): ?UserNotification;

    /**
     * Find a user notification by ID or fail
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): UserNotification;

    /**
     * Delete a user notification
     */
    public function delete(int $id): bool;

    /**
     * Get a query builder instance
     *
     * @return Builder<UserNotification>
     */
    public function query(): Builder;

    /**
     * Get user notifications for a specific user
     *
     * @return Builder<UserNotification>
     */
    public function forUser(int $userId): Builder;

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(int $userId): int;

    /**
     * Mark notification as read
     */
    public function markAsRead(int $id): bool;

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Get user notifications with pagination
     *
     * @param  array<string, mixed>  $params
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function paginate(array $params): LengthAwarePaginator;
}
