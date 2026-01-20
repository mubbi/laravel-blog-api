<?php

declare(strict_types=1);

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationCreatedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleNotificationCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(NotificationCreatedEvent $event): void
    {
        Log::info(__('log.notification_created'), [
            'notification_id' => $event->notification->id,
            'type' => $event->notification->type->value,
        ]);

        // Distribute notification to users by creating UserNotification records
        $distributedCount = $this->notificationService->distributeToUsers($event->notification);

        Log::info(__('log.notification_distributed'), [
            'notification_id' => $event->notification->id,
            'distributed_count' => $distributedCount,
        ]);
    }
}
