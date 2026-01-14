<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserUnfollowedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserUnfollowedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserUnfollowedEvent $event): void
    {
        Log::info(__('log.user_unfollowed'), [
            'follower_id' => $event->follower->id,
            'unfollowed_id' => $event->unfollowed->id,
            'follower_name' => $event->follower->name,
            'unfollowed_name' => $event->unfollowed->name,
        ]);

        // Add your business logic here
        // For example: Update statistics, etc.
    }
}
