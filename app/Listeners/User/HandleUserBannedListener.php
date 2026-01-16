<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Data\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\User\UserBannedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserBannedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserBannedEvent $event): void
    {
        Log::info(__('log.user_banned'), [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'banned_at' => $event->user->banned_at,
        ]);

        // Create notification for banned user
        $user = $event->user;

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.user_banned.title'),
                'body' => __('notifications.user_banned.body'),
                'priority' => 'high',
            ],
            audiences: ['specific_users'],
            userIds: [$user->id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'user_id' => $user->id,
        ]);
    }
}
