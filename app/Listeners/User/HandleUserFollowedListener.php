<?php

declare(strict_types=1);

namespace App\Listeners\User;

use App\Events\User\UserFollowedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleUserFollowedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(UserFollowedEvent $event): void
    {
        Log::info(__('log.user_followed'), [
            'follower_id' => $event->follower->id,
            'followed_id' => $event->followed->id,
            'follower_name' => $event->follower->name,
            'followed_name' => $event->followed->name,
        ]);

        // Add your business logic here
        // For example: Send notification to followed user, update statistics, etc.
    }
}
