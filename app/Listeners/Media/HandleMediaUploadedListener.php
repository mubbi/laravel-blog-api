<?php

declare(strict_types=1);

namespace App\Listeners\Media;

use App\Events\Media\MediaUploadedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleMediaUploadedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(MediaUploadedEvent $event): void
    {
        Log::info(__('log.media_uploaded'), [
            'media_id' => $event->media->id,
            'media_name' => $event->media->name,
            'media_type' => $event->media->type,
            'file_name' => $event->media->file_name,
            'file_size' => $event->media->size,
            'uploaded_by' => $event->media->uploaded_by,
            'disk' => $event->media->disk,
            'path' => $event->media->path,
        ]);

        // Add your business logic here
        // For example: Update search index, send notifications, etc.
    }
}
