<?php

declare(strict_types=1);

namespace App\Listeners\Comment;

use App\Data\CreateNotificationDTO;
use App\Enums\CommentStatus;
use App\Enums\NotificationType;
use App\Events\Comment\CommentCreatedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleCommentCreatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

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

        $comment = $event->comment;

        // Only send notification if comment is approved or auto-approved
        if ($comment->status !== CommentStatus::APPROVED) {
            return;
        }

        // Load article, author, and commenter relationships
        if (! $comment->relationLoaded('article')) {
            $comment->load('article.author');
        }
        if (! $comment->relationLoaded('user')) {
            $comment->load('user');
        }

        $article = $comment->article;
        $author = $article->author ?? null;

        // Don't notify if article author is the same as comment author
        if ($author === null || $author->id === $comment->user_id) {
            return;
        }

        // We already checked that user is loaded and not null above
        $commenterName = $comment->user->name ?? 'Anonymous';

        // Create notification for article author
        $dto = new CreateNotificationDTO(
            type: NotificationType::NEW_COMMENT,
            message: [
                'title' => __('notifications.new_comment.title'),
                'body' => __('notifications.new_comment.body', [
                    'article_title' => $article->title,
                    'commenter_name' => $commenterName,
                ]),
                'priority' => 'normal',
            ],
            audiences: ['specific_users'],
            userIds: [$author->id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::NEW_COMMENT->value,
            'comment_id' => $comment->id,
            'article_id' => $article->id,
            'user_id' => $author->id,
        ]);
    }
}
