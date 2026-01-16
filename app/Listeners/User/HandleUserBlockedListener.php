<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Data\Notification\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\User\UserBlockedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserBlockedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserBlockedEvent $event): void
    {
        Log::info(__('log.user_blocked'), [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'blocked_at' => $event->user->blocked_at,
        ]);

        // Create notification for blocked user
        $user = $event->user;

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.user_blocked.title'),
                'body' => __('notifications.user_blocked.body'),
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
