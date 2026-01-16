<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Data\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\User\UserFollowedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserFollowedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(UserFollowedEvent $event): void
    {
        Log::info(__('log.user_followed'), [
            'follower_id' => $event->follower->id,
            'followed_id' => $event->followed->id,
            'follower_name' => $event->follower->name,
            'followed_name' => $event->followed->name,
        ]);

        // Create notification for followed user
        $follower = $event->follower;
        $followed = $event->followed;

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.user_followed.title'),
                'body' => __('notifications.user_followed.body', ['follower_name' => $follower->name]),
                'priority' => 'normal',
            ],
            audiences: ['specific_users'],
            userIds: [$followed->id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);
    }
}
