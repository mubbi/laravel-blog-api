<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticlePinnedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticlePinnedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticlePinnedEvent $event): void
    {
        Log::info('Article pinned', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Update pinned articles cache, send notification, etc.
    }
}
