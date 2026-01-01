<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\UserLoggedInEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserLoggedInListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserLoggedInEvent $event): void
    {
        Log::info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Add your business logic here
        // For example: Track login activity, send notifications, etc.
    }
}
