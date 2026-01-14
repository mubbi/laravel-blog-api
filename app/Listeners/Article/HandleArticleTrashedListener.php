<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleTrashedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleTrashedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleTrashedEvent $event): void
    {
        Log::info(__('log.article_trashed'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Remove from indexes, send notification, etc.
    }
}
