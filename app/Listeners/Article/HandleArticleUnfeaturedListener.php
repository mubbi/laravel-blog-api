<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Article\ArticleUnfeaturedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleUnfeaturedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ArticleUnfeaturedEvent $event): void
    {
        Log::info('Article unfeatured', [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
        ]);

        // Add your business logic here
        // For example: Clear featured articles cache, etc.
    }
}
