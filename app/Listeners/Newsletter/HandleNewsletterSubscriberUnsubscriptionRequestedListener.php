<?php

declare(strict_types=1);

namespace App\Listeners\Newsletter;

use App\Events\Newsletter\NewsletterSubscriberUnsubscriptionRequestedEvent;
use App\Mail\NewsletterUnsubscriptionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

final class HandleNewsletterSubscriberUnsubscriptionRequestedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NewsletterSubscriberUnsubscriptionRequestedEvent $event): void
    {
        Mail::to($event->email)->send(
            new NewsletterUnsubscriptionMail(
                email: $event->email,
                unsubscriptionToken: $event->unsubscriptionToken
            )
        );
    }
}
