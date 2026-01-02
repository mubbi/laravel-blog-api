<?php

declare(strict_types=1);

namespace App\Listeners\Newsletter;

use App\Events\Newsletter\NewsletterSubscriberCreatedEvent;
use App\Mail\NewsletterVerificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

final class HandleNewsletterSubscriberCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(NewsletterSubscriberCreatedEvent $event): void
    {
        Mail::to($event->email)->send(
            new NewsletterVerificationMail(
                email: $event->email,
                verificationToken: $event->verificationToken
            )
        );
    }
}
