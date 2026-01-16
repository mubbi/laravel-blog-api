<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Data\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Comment\CommentApprovedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentApprovedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CommentApprovedEvent $event): void
    {
        Log::info(__('log.comment_approved'), [
            'comment_id' => $event->comment->id,
            'article_id' => $event->comment->article_id,
            'user_id' => $event->comment->user_id,
        ]);

        $comment = $event->comment;

        // Only send notification if comment has a user
        if ($comment->user_id === null) {
            return;
        }

        // Load article relationship
        if (! $comment->relationLoaded('article')) {
            $comment->load('article');
        }

        $article = $comment->article;

        // Create notification for comment author when comment is approved
        // We already checked that user_id is not null above
        assert($comment->user_id !== null);
        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.comment_approved.title'),
                'body' => __('notifications.comment_approved.body', ['article_title' => $article->title]),
                'priority' => 'normal',
            ],
            audiences: ['specific_users'],
            userIds: [$comment->user_id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'comment_id' => $comment->id,
            'user_id' => $comment->user_id,
        ]);
    }
}
