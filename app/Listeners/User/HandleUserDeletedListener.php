<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserDeletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserDeletedEvent $event): void
    {
        Log::info(__('log.user_deleted'), [
            'user_id' => $event->userId,
            'email' => $event->email,
        ]);

        // Add your business logic here
        // For example: Clean up related data, send deletion notification, etc.
    }
}
