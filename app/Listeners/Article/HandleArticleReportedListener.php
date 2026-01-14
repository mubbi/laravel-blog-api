<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleReportedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleReportedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleReportedEvent $event): void
    {
        Log::info(__('log.article_reported'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'report_count' => $event->article->report_count,
        ]);

        // Add your business logic here
        // For example: Send notification to moderators, review article, etc.
    }
}
