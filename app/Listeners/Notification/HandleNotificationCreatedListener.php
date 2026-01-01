<?php

declare(strict_types=1);

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleNotificationCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NotificationCreatedEvent $event): void
    {
        Log::info('Notification created', [
            'notification_id' => $event->notification->id,
            'type' => $event->notification->type->value,
        ]);

        // Add your business logic here
        // For example: Distribute to users, prepare for sending, etc.
    }
}
