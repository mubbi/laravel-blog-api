<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Events\Comment\CommentCreatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentCreatedEvent $event): void
    {
        Log::info(__('log.comment_created'), [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
            'status' => $event->comment->status,
        ]);

        // Add your business logic here
        // For example: Send notification to article author, moderate comment, etc.
    }
}
