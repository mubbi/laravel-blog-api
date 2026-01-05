<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleDislikedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleDislikedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleDislikedEvent $event): void
    {
        Log::info(__('log.article_disliked'), [
            'article_id' => $event->article->id,
            'article_title' => $event->article->title,
            'dislike_id' => $event->dislike->id,
            'user_id' => $event->dislike->user_id,
            'ip_address' => $event->dislike->ip_address,
        ]);

        // Add your business logic here
        // For example: Update statistics, etc.
    }
}
