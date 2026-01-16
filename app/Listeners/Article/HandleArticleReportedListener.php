<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Data\CreateNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Article\ArticleReportedEvent;
use App\Services\Interfaces\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

final class HandleArticleReportedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ArticleReportedEvent $event): void
    {
        Log::info(__('log.article_reported'), [
            'article_id' => $event->article->id,
            'title' => $event->article->title,
            'report_count' => $event->article->report_count,
        ]);

        // Create notification for administrators when article is reported
        $article = $event->article;

        $dto = new CreateNotificationDTO(
            type: NotificationType::SYSTEM_ALERT,
            message: [
                'title' => __('notifications.article_reported.title'),
                'body' => __('notifications.article_reported.body', [
                    'title' => $article->title,
                    'report_count' => $article->report_count,
                ]),
                'priority' => 'high',
            ],
            audiences: ['administrators'],
            userIds: null,
        );

        $this->notificationService->createNotification($dto);

        Log::info(__('log.notification_created'), [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'article_id' => $article->id,
            'audience' => 'administrators',
        ]);
    }
}
