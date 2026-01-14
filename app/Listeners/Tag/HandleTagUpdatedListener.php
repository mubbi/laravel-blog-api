<?php

declare(strict_types=1);

namespace App\Listeners\Tag;

use App\Events\Tag\TagUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleTagUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TagUpdatedEvent $event): void
    {
        Log::info(__('log.tag_updated'), [
            'tag_id' => $event->tag->id,
            'tag_name' => $event->tag->name,
            'tag_slug' => $event->tag->slug,
        ]);

        // Add your business logic here
        // For example: Update search index, invalidate cache, etc.
    }
}
