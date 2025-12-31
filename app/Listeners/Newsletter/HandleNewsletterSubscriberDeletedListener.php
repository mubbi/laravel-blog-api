<?php

declare(strict_types=1);

namespace App\Listeners\Newsletter;

use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleNewsletterSubscriberDeletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NewsletterSubscriberDeletedEvent $event): void
    {
        Log::info('Newsletter subscriber deleted', [
            'subscriber_id' => $event->subscriberId,
            'email' => $event->email,
        ]);

        // Add your business logic here
        // For example: Remove from external mailing list, update statistics, etc.
    }
}
