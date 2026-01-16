<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Data\Notification\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Article\ArticleApprovedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleApprovedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ArticleApprovedEvent $event): void
    {
        Log::info(__('log.article_approved'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'approved_by' => $event->article->approved_by,
        ]);

        // Create notification for article author when article is approved
        $article = $event->article;
        $author = $article->author;

        if ($author === null) {
            return;
        }

        $dto = new CreateNotificationDTO(
            type: NotificationType::ARTICLE_PUBLISHED,
            message: [
                'title' => __('notifications.article_published.title'),
                'body' => __('notifications.article_published.body', ['title' => $article->title]),
                'priority' => 'normal',
            ],
            audiences: ['specific_users'],
            userIds: [$author->id],
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::ARTICLE_PUBLISHED->value,
            'article_id' => $article->id,
            'user_id' => $author->id,
        ]);
    }
}
