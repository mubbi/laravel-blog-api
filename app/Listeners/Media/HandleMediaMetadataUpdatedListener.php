<?php

declare(strict_types=1);

namespace App\Listeners\Media;

use App\Events\Media\MediaMetadataUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleMediaMetadataUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MediaMetadataUpdatedEvent $event): void
    {
        Log::info(__('log.media_metadata_updated'), [
            'media_id' => $event->media->id,
            'media_name' => $event->media->name,
            'file_name' => $event->media->file_name,
            'updated_fields' => [
                'name' => $event->media->name,
                'alt_text' => $event->media->alt_text,
                'caption' => $event->media->caption,
                'description' => $event->media->description,
            ],
        ]);

        // Add your business logic here
        // For example: Update search index, invalidate cache, etc.
    }
}
