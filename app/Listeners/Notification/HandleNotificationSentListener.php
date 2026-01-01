<?php

declare(strict_types=1);

namespace App\Listeners\Notification;

use App\Events\Notification\NotificationSentEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleNotificationSentListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NotificationSentEvent $event): void
    {
        Log::info('Notification sent', [
            'notification_id' => $event->notification->id,
            'type' => $event->notification->type->value,
        ]);

        // Add your business logic here
        // For example: Track delivery, update statistics, etc.
    }
}
