<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleArchivedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleArchivedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleArchivedEvent $event): void
    {
        Log::info('Article archived', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Send notification, remove from public indexes, etc.
    }
}
