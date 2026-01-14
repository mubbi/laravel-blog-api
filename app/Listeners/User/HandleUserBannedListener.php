<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserBannedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserBannedListener implements ShouldQueue
{
    use InteractsWithQueue;

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

        // Add your business logic here
        // For example: Revoke tokens, send notification, etc.
    }
}
