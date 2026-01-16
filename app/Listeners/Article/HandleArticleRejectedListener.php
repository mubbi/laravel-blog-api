<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Data\Notification\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Article\ArticleRejectedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleRejectedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ArticleRejectedEvent $event): void
    {
        Log::info(__('log.article_rejected'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'approved_by' => $event->article->approved_by,
        ]);

        // Create notification for article author when article is rejected
        $article = $event->article;
        $author = $article->author;

        if ($author === null) {
            return;
        }

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.article_rejected.title'),
                'body' => __('notifications.article_rejected.body', ['title' => $article->title]),
                'priority' => 'high',
            ],
            audiences: ['specific_users'],
            userIds: [$author->id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'article_id' => $article->id,
            'user_id' => $author->id,
        ]);
    }
}
