<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleRestoredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleRestoredListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleRestoredEvent $event): void
    {
        Log::info('Article restored', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Re-index article, send notification, etc.
    }
}
