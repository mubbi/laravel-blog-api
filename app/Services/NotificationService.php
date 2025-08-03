<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationService
{
    /**
     * Create a new notification
     *
     * @param  array<string, mixed>  $data
     */
    public function createNotification(array $data): Notification
    {
        // Ensure we store the complete message structure
        $notification = Notification::create([
            'type' => $data['type'],
            'message' => $data['message'], // This should contain the complete message structure
        ]);

        // Create audience records
        if (isset($data['audiences']) && is_array($data['audiences'])) {
            foreach ($data['audiences'] as $audience) {
                if ($audience === 'specific_users' && isset($data['user_ids']) && is_array($data['user_ids'])) {
                    foreach ($data['user_ids'] as $userId) {
                        NotificationAudience::create([
                            'notification_id' => $notification->id,
                            'audience_type' => 'user',
                            'audience_id' => $userId,
                        ]);
                    }
                } elseif ($audience === 'administrators') {
                    $adminRole = Role::where('name', 'administrator')->first();
                    if ($adminRole) {
                        NotificationAudience::create([
                            'notification_id' => $notification->id,
                            'audience_type' => 'role',
                            'audience_id' => $adminRole->id,
                        ]);
                    }
                } elseif ($audience === 'all_users') {
                    NotificationAudience::create([
                        'notification_id' => $notification->id,
                        'audience_type' => 'all',
                        'audience_id' => null,
                    ]);
                }
            }
        }

        return $notification->load('audiences');
    }

    /**
     * Send a notification
     */
    public function sendNotification(Notification $notification): void
    {
        // Here you would implement the actual sending logic
        // This could involve:
        // - Sending emails
        // - Sending push notifications
        // - Sending SMS
        // - Creating in-app notifications
        // - etc.
    }

    /**
     * Get notification by ID
     *
     * @throws ModelNotFoundException
     */
    public function getNotificationById(int $notificationId): Notification
    {
        return Notification::with(['audiences'])->findOrFail($notificationId);
    }

    /**
     * Get notifications with filters
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Notification>
     */
    public function getNotifications(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Notification::query();

        if (isset($filters['search'])) {
            /** @var string $searchTerm */
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("JSON_EXTRACT(message, '$.title') LIKE ?", ["%{$searchTerm}%"])
                    ->orWhereRaw("JSON_EXTRACT(message, '$.body') LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['created_at_from'])) {
            $query->where('created_at', '>=', $filters['created_at_from']);
        }

        if (isset($filters['created_at_to'])) {
            $query->where('created_at', '<=', $filters['created_at_to']);
        }

        /** @var string $sortBy */
        $sortBy = $filters['sort_by'] ?? 'created_at';
        /** @var string $sortOrder */
        $sortOrder = $filters['sort_order'] ?? 'desc';
        /** @var int $perPage */
        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get total notification count
     */
    public function getTotalNotifications(): int
    {
        return Notification::count();
    }

    /**
     * Get notification statistics
     *
     * @return array<string, int|array<string, int>>
     */
    public function getNotificationStats(): array
    {
        return [
            'total' => Notification::count(),
            'by_type' => [
                'article_published' => Notification::where('type', NotificationType::ARTICLE_PUBLISHED)->count(),
                'new_comment' => Notification::where('type', NotificationType::NEW_COMMENT)->count(),
                'newsletter' => Notification::where('type', NotificationType::NEWSLETTER)->count(),
                'system_alert' => Notification::where('type', NotificationType::SYSTEM_ALERT)->count(),
            ],
        ];
    }
}
