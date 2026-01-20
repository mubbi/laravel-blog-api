<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Notification\CreateNotificationDTO;
use App\Data\Notification\FilterNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Notification\NotificationCreatedEvent;
use App\Events\Notification\NotificationSentEvent;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationAudienceRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserNotificationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly NotificationAudienceRepositoryInterface $notificationAudienceRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly UserNotificationRepositoryInterface $userNotificationRepository,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Create a new notification
     */
    public function createNotification(CreateNotificationDTO $dto): Notification
    {
        $notification = DB::transaction(function () use ($dto) {
            $notification = $this->notificationRepository->create($dto->toArray());

            // Create audience records
            foreach ($dto->audiences as $audience) {
                if ($audience === 'specific_users' && $dto->userIds !== null) {
                    foreach ($dto->userIds as $userId) {
                        $this->notificationAudienceRepository->create([
                            'notification_id' => $notification->id,
                            'audience_type' => 'user',
                            'audience_id' => $userId,
                        ]);
                    }
                } elseif ($audience === 'administrators') {
                    $adminRole = $this->roleRepository->findByName('administrator');
                    if ($adminRole !== null) {
                        $this->notificationAudienceRepository->create([
                            'notification_id' => $notification->id,
                            'audience_type' => 'role',
                            'audience_id' => $adminRole->id,
                        ]);
                    }
                } elseif ($audience === 'all_users') {
                    $this->notificationAudienceRepository->create([
                        'notification_id' => $notification->id,
                        'audience_type' => 'all',
                        'audience_id' => null,
                    ]);
                }
            }

            $notification->load('audiences');

            return $notification;
        });

        Event::dispatch(new NotificationCreatedEvent($notification));

        return $notification;
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

        Event::dispatch(new NotificationSentEvent($notification));
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
        $query = $this->notificationRepository->query()
            ->with(['audiences']);

        $this->applyFilters($query, $dto);

        return $query->orderBy($dto->sortBy, $dto->sortOrder)->paginate($dto->perPage);
    }

    /**
     * Apply filters to the query
     *
     * @param  Builder<Notification>  $query
     */
    private function applyFilters(Builder $query, FilterNotificationDTO $dto): void
    {
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
                'article_published' => $this->notificationRepository->countByType(NotificationType::ARTICLE_PUBLISHED->value),
                'new_comment' => $this->notificationRepository->countByType(NotificationType::NEW_COMMENT->value),
                'newsletter' => $this->notificationRepository->countByType(NotificationType::NEWSLETTER->value),
                'system_alert' => $this->notificationRepository->countByType(NotificationType::SYSTEM_ALERT->value),
            ],
        ];
    }

    /**
     * Distribute notification to users by creating UserNotification records
     *
     * @return int Number of UserNotification records created
     */
    public function distributeToUsers(Notification $notification): int
    {
        // Ensure audiences are loaded
        if (! $notification->relationLoaded('audiences')) {
            $notification->load('audiences');
        }

        $userIds = $this->resolveUserIds($notification);

        if (empty($userIds)) {
            return 0;
        }

        // Remove duplicates
        $userIds = array_unique($userIds);

        // Get existing UserNotification records to avoid duplicates
        $existingUserIds = $this->userNotificationRepository->query()
            ->where('notification_id', $notification->id)
            ->whereIn('user_id', $userIds)
            ->pluck('user_id')
            ->toArray();

        // Filter out users who already have this notification
        $newUserIds = array_diff($userIds, $existingUserIds);

        if (empty($newUserIds)) {
            return 0;
        }

        // Bulk create UserNotification records
        $userNotifications = [];
        foreach ($newUserIds as $userId) {
            $userNotifications[] = [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Use chunking for large batches to avoid memory issues
        $chunks = array_chunk($userNotifications, 500);
        $totalCreated = 0;

        foreach ($chunks as $chunk) {
            DB::table('user_notifications')->insert($chunk);
            $totalCreated += count($chunk);
        }

        return $totalCreated;
    }

    /**
     * Resolve user IDs from notification audiences
     *
     * @return array<int>
     */
    private function resolveUserIds(Notification $notification): array
    {
        /** @var array<int> $userIds */
        $userIds = [];

        foreach ($notification->audiences as $audience) {
            if ($audience->audience_type === 'all') {
                // Get all active users
                /** @var array<int> $allUserIds */
                $allUserIds = $this->userRepository->query()
                    ->whereNull('banned_at')
                    ->whereNull('blocked_at')
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->toArray();
                $userIds = array_merge($userIds, $allUserIds);
            } elseif ($audience->audience_type === 'role' && $audience->audience_id !== null) {
                // Get users with specific role
                /** @var array<int> $roleUserIds */
                $roleUserIds = $this->userRepository->query()
                    ->whereHas('roles', function (Builder $query) use ($audience): void {
                        $query->where('roles.id', $audience->audience_id);
                    })
                    ->whereNull('banned_at')
                    ->whereNull('blocked_at')
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->toArray();
                $userIds = array_merge($userIds, $roleUserIds);
            } elseif ($audience->audience_type === 'user' && $audience->audience_id !== null) {
                // Specific user
                $userIds[] = (int) $audience->audience_id;
            }
        }

        return $userIds;
    }
}
