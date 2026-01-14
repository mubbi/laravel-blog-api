<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleCreatedEvent $event): void
    {
        Log::info(__('log.article_created'), [
            'article_id' => $event->article->id,
            'article_title' => $event->article->title,
            'article_slug' => $event->article->slug,
            'author_id' => $event->article->created_by,
            'status' => $event->article->status,
        ]);

        // Add your business logic here
        // For example: Send notifications, update statistics, etc.
    }
}
