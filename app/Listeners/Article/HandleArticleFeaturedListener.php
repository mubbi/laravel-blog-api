<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleFeaturedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleFeaturedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleFeaturedEvent $event): void
    {
        Log::info(__('log.article_featured'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Send notification, update featured articles cache, etc.
    }
}
