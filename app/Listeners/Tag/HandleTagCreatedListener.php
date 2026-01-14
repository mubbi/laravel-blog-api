<?php

declare(strict_types=1);

namespace App\Listeners\Tag;

use App\Events\Tag\TagCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleTagCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TagCreatedEvent $event): void
    {
        Log::info(__('log.tag_created'), [
            'tag_id' => $event->tag->id,
            'tag_name' => $event->tag->name,
            'tag_slug' => $event->tag->slug,
        ]);

        // Add your business logic here
        // For example: Update search index, etc.
    }
}
