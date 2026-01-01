<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserCreatedEvent $event): void
    {
        Log::info('User created', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'name' => $event->user->name,
        ]);

        // Add your business logic here
        // For example: Send welcome email, create user profile, etc.
    }
}
