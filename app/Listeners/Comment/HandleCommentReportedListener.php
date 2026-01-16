<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Data\Notification\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Comment\CommentReportedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentReportedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

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

        // Create notification for administrators when comment is reported
        $comment = $event->comment;

        // Load article relationship if needed
        if (! $comment->relationLoaded('article')) {
            $comment->load('article');
        }

        $article = $comment->article;

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.comment_reported.title'),
                'body' => __('notifications.comment_reported.body', [
                    'article_title' => $article->title,
                    'report_count' => $comment->report_count,
                ]),
                'priority' => 'high',
            ],
            audiences: ['administrators'],
            userIds: null,
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'comment_id' => $comment->id,
            'audience' => 'administrators',
        ]);
    }
}
