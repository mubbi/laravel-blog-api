<?php

declare(strict_types=1);

namespace App\Listeners\Category;

use App\Events\Category\CategoryCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCategoryCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CategoryCreatedEvent $event): void
    {
        Log::info(__('log.category_created'), [
            'category_id' => $event->category->id,
            'category_name' => $event->category->name,
            'category_slug' => $event->category->slug,
            'parent_id' => $event->category->parent_id,
        ]);

        // Add your business logic here
        // For example: Update search index, etc.
    }
}
