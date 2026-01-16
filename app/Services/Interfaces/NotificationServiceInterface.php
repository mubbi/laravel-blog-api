<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\Notification\CreateNotificationDTO;
use App\Data\Notification\FilterNotificationDTO;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationServiceInterface
{
    /**
     * Create a new notification
     */
    public function createNotification(CreateNotificationDTO $dto): Notification;

    /**
     * Send a notification
     */
    public function sendNotification(Notification $notification): void;

    /**
     * Get notification by ID
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getNotificationById(int $notificationId): Notification;

    /**
     * Get notifications with filters
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function getNotifications(FilterNotificationDTO $dto): LengthAwarePaginator;

    /**
     * Get total notification count
     */
    public function getTotalNotifications(): int;

    /**
     * Get notification statistics
     *
     * @return array<string, int|array<string, int>>
     */
    public function getNotificationStats(): array;

    /**
     * Distribute notification to users by creating UserNotification records
     *
     * @return int Number of UserNotification records created
     */
    public function distributeToUsers(Notification $notification): int;
}
