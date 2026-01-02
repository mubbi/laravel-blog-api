<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleApprovedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleApprovedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleApprovedEvent $event): void
    {
        Log::info(__('log.article_approved'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'approved_by' => $event->article->approved_by,
        ]);

        // Add your business logic here
        // For example: Send notification to author, publish to external services, etc.
    }
}
