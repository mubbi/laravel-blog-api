<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleDeletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleDeletedEvent $event): void
    {
        Log::info(__('log.article_deleted'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Clean up related data, remove from external services, etc.
    }
}
