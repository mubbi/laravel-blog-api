<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleLikedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleLikedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleLikedEvent $event): void
    {
        Log::info(__('log.article_liked'), [
            'article_id' => $event->article->id,
            'article_title' => $event->article->title,
            'like_id' => $event->like->id,
            'user_id' => $event->like->user_id,
            'ip_address' => $event->like->ip_address,
        ]);

        // Add your business logic here
        // For example: Send notification to article author, update statistics, etc.
    }
}
