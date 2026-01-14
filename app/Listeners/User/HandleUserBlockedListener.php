<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserBlockedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserBlockedListener implements ShouldQueue
{
    use InteractsWithQueue;

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

        // Add your business logic here
        // For example: Send notification, restrict access, etc.
    }
}
