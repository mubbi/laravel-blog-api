<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CreateNotificationDTO;
use App\Data\FilterNotificationDTO;
use App\Models\Notification;
use App\Models\NotificationAudience;
use App\Models\Role;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Create a new notification
     */
    public function createNotification(CreateNotificationDTO $dto): Notification
    {
        return DB::transaction(function () use ($dto) {
            $notification = $this->notificationRepository->create($dto->toArray());

            // Create audience records
            foreach ($dto->audiences as $audience) {
                if ($audience === 'specific_users' && $dto->userIds !== null) {
                    foreach ($dto->userIds as $userId) {
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

            return $notification->load('audiences');
        });
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
        return $this->notificationRepository->query()
            ->with(['audiences'])
            ->findOrFail($notificationId);
    }

    /**
     * Get notifications with filters
     *
     * @return LengthAwarePaginator<int, Notification>
     */
    public function getNotifications(FilterNotificationDTO $dto): LengthAwarePaginator
    {
        $query = $this->notificationRepository->query();

        if ($dto->search !== null) {
            $query->where(function (Builder $q) use ($dto) {
                $q->whereRaw("JSON_EXTRACT(message, '$.title') LIKE ?", ["%{$dto->search}%"])
                    ->orWhereRaw("JSON_EXTRACT(message, '$.body') LIKE ?", ["%{$dto->search}%"]);
            });
        }

        if ($dto->type !== null) {
            $query->where('type', $dto->type->value);
        }

        if ($dto->createdAtFrom !== null) {
            $query->where('created_at', '>=', $dto->createdAtFrom);
        }

        if ($dto->createdAtTo !== null) {
            $query->where('created_at', '<=', $dto->createdAtTo);
        }

        return $query->orderBy($dto->sortBy, $dto->sortOrder)->paginate($dto->perPage);
    }

    /**
     * Get total notification count
     */
    public function getTotalNotifications(): int
    {
        return $this->notificationRepository->count();
    }

    /**
     * Get notification statistics
     *
     * @return array<string, int|array<string, int>>
     */
    public function getNotificationStats(): array
    {
        return [
            'total' => $this->notificationRepository->count(),
            'by_type' => [
                'article_published' => $this->notificationRepository->countByType(\App\Enums\NotificationType::ARTICLE_PUBLISHED->value),
                'new_comment' => $this->notificationRepository->countByType(\App\Enums\NotificationType::NEW_COMMENT->value),
                'newsletter' => $this->notificationRepository->countByType(\App\Enums\NotificationType::NEWSLETTER->value),
                'system_alert' => $this->notificationRepository->countByType(\App\Enums\NotificationType::SYSTEM_ALERT->value),
            ],
        ];
    }
}
