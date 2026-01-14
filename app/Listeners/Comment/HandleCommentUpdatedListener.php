<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Events\Comment\CommentUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentUpdatedEvent $event): void
    {
        Log::info(__('log.comment_updated'), [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
        ]);

        // Add your business logic here
        // For example: Update statistics, etc.
    }
}
