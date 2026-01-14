<?php

declare(strict_types=1);

namespace App\Listeners\Auth;

use App\Events\Auth\TokenRefreshedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleTokenRefreshedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TokenRefreshedEvent $event): void
    {
        Log::info(__('log.token_refreshed'), [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Add your business logic here
        // For example: Track token refresh activity, security monitoring, etc.
    }
}
