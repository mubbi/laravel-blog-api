<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Events\Comment\CommentDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentDeletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentDeletedEvent $event): void
    {
        Log::info(__('log.comment_deleted'), [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
        ]);

        // Add your business logic here
        // For example: Update article comment count, send notification, etc.
    }
}
