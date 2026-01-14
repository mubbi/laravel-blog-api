<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserUpdatedEvent $event): void
    {
        Log::info(__('log.user_updated'), [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
        ]);

        // Add your business logic here
        // For example: Clear caches, sync external services, etc.
    }
}
