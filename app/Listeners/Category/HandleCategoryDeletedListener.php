<?php

declare(strict_types=1);

namespace App\Listeners\Category;

use App\Events\Category\CategoryDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCategoryDeletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CategoryDeletedEvent $event): void
    {
        Log::info(__('log.category_deleted'), [
            'category_id' => $event->category->id,
            'category_name' => $event->category->name,
            'category_slug' => $event->category->slug,
        ]);

        // Add your business logic here
        // For example: Update search index, etc.
    }
}
