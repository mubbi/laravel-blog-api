<?php

declare(strict_types=1);

namespace App\Events\Newsletter;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

final class NewsletterSubscriberDeletedEvent
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $subscriberId,
        public readonly string $email
    ) {}
}
