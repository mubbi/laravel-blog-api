<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleRejectedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleRejectedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleRejectedEvent $event): void
    {
        Log::info('Article rejected', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'approved_by' => $event->article->approved_by,
        ]);

        // Add your business logic here
        // For example: Send notification to author with rejection reason, etc.
    }
}
