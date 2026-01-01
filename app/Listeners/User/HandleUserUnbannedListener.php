<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserUnbannedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserUnbannedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserUnbannedEvent $event): void
    {
        Log::info('User unbanned', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Add your business logic here
        // For example: Send notification, restore access, etc.
    }
}
