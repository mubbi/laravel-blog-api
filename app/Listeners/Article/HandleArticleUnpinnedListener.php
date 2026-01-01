<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleUnpinnedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleUnpinnedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleUnpinnedEvent $event): void
    {
        Log::info('Article unpinned', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Clear pinned articles cache, etc.
    }
}
