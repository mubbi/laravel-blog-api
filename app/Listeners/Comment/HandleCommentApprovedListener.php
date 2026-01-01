<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Events\Comment\CommentApprovedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentApprovedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentApprovedEvent $event): void
    {
        Log::info('Comment approved', [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
        ]);

        // Add your business logic here
        // For example: Send notification to comment author, update article comment count, etc.
    }
}
