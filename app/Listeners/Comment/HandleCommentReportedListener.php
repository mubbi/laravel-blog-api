<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Events\Comment\CommentReportedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentReportedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentReportedEvent $event): void
    {
        Log::info(__('log.comment_reported'), [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
            'report_count' => $event->comment->report_count,
            'report_reason' => $event->comment->report_reason,
        ]);

        // Add your business logic here
        // For example: Send notification to moderators, auto-moderate if threshold reached, etc.
    }
}
