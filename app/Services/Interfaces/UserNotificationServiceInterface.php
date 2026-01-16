<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\FilterUserNotificationDTO;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserNotificationServiceInterface
{
    /**
     * Get user's notifications with filters
     *
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function getUserNotifications(User $user, FilterUserNotificationDTO $dto): LengthAwarePaginator;

    /**
     * Mark a notification as read
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsRead(User $user, int $userNotificationId): UserNotification;

    /**
     * Delete a user notification
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteNotification(User $user, int $userNotificationId): bool;

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int;

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(User $user): int;
}
