<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\UserLoggedOutEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserLoggedOutListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserLoggedOutEvent $event): void
    {
        Log::info('User logged out', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Add your business logic here
        // For example: Track logout activity, cleanup sessions, etc.
    }
}
