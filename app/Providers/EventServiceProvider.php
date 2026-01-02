<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Article\ArticleApprovedEvent;
use App\Events\Article\ArticleArchivedEvent;
use App\Events\Article\ArticleDeletedEvent;
use App\Events\Article\ArticleFeaturedEvent;
use App\Events\Article\ArticlePinnedEvent;
use App\Events\Article\ArticleRejectedEvent;
use App\Events\Article\ArticleReportedEvent;
use App\Events\Article\ArticleReportsClearedEvent;
use App\Events\Article\ArticleRestoredEvent;
use App\Events\Article\ArticleRestoredFromTrashEvent;
use App\Events\Article\ArticleTrashedEvent;
use App\Events\Article\ArticleUnfeaturedEvent;
use App\Events\Article\ArticleUnpinnedEvent;
use App\Events\Auth\TokenRefreshedEvent;
use App\Events\Auth\UserLoggedInEvent;
use App\Events\Auth\UserLoggedOutEvent;
use App\Events\Comment\CommentApprovedEvent;
use App\Events\Comment\CommentDeletedEvent;
use App\Events\Newsletter\NewsletterSubscriberCreatedEvent;
use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use App\Events\Newsletter\NewsletterSubscriberUnsubscriptionRequestedEvent;
use App\Events\Notification\NotificationCreatedEvent;
use App\Events\Notification\NotificationSentEvent;
use App\Events\User\UserBannedEvent;
use App\Events\User\UserBlockedEvent;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserDeletedEvent;
use App\Events\User\UserUnbannedEvent;
use App\Events\User\UserUnblockedEvent;
use App\Events\User\UserUpdatedEvent;
use App\Listeners\Article\HandleArticleApprovedListener;
use App\Listeners\Article\HandleArticleArchivedListener;
use App\Listeners\Article\HandleArticleDeletedListener;
use App\Listeners\Article\HandleArticleFeaturedListener;
use App\Listeners\Article\HandleArticlePinnedListener;
use App\Listeners\Article\HandleArticleRejectedListener;
use App\Listeners\Article\HandleArticleReportedListener;
use App\Listeners\Article\HandleArticleReportsClearedListener;
use App\Listeners\Article\HandleArticleRestoredFromTrashListener;
use App\Listeners\Article\HandleArticleRestoredListener;
use App\Listeners\Article\HandleArticleTrashedListener;
use App\Listeners\Article\HandleArticleUnfeaturedListener;
use App\Listeners\Article\HandleArticleUnpinnedListener;
use App\Listeners\Auth\HandleTokenRefreshedListener;
use App\Listeners\Auth\HandleUserLoggedInListener;
use App\Listeners\Auth\HandleUserLoggedOutListener;
use App\Listeners\Comment\HandleCommentApprovedListener;
use App\Listeners\Comment\HandleCommentDeletedListener;
use App\Listeners\Newsletter\HandleNewsletterSubscriberCreatedListener;
use App\Listeners\Newsletter\HandleNewsletterSubscriberDeletedListener;
use App\Listeners\Newsletter\HandleNewsletterSubscriberUnsubscriptionRequestedListener;
use App\Listeners\Notification\HandleNotificationCreatedListener;
use App\Listeners\Notification\HandleNotificationSentListener;
use App\Listeners\User\HandleUserBannedListener;
use App\Listeners\User\HandleUserBlockedListener;
use App\Listeners\User\HandleUserCreatedListener;
use App\Listeners\User\HandleUserDeletedListener;
use App\Listeners\User\HandleUserUnbannedListener;
use App\Listeners\User\HandleUserUnblockedListener;
use App\Listeners\User\HandleUserUpdatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // User Events
        UserCreatedEvent::class => [
            HandleUserCreatedListener::class,
        ],
        UserUpdatedEvent::class => [
            HandleUserUpdatedListener::class,
        ],
        UserDeletedEvent::class => [
            HandleUserDeletedListener::class,
        ],
        UserBannedEvent::class => [
            HandleUserBannedListener::class,
        ],
        UserUnbannedEvent::class => [
            HandleUserUnbannedListener::class,
        ],
        UserBlockedEvent::class => [
            HandleUserBlockedListener::class,
        ],
        UserUnblockedEvent::class => [
            HandleUserUnblockedListener::class,
        ],

        // Auth Events
        UserLoggedInEvent::class => [
            HandleUserLoggedInListener::class,
        ],
        UserLoggedOutEvent::class => [
            HandleUserLoggedOutListener::class,
        ],
        TokenRefreshedEvent::class => [
            HandleTokenRefreshedListener::class,
        ],

        // Article Events
        ArticleApprovedEvent::class => [
            HandleArticleApprovedListener::class,
        ],
        ArticleRejectedEvent::class => [
            HandleArticleRejectedListener::class,
        ],
        ArticleFeaturedEvent::class => [
            HandleArticleFeaturedListener::class,
        ],
        ArticleUnfeaturedEvent::class => [
            HandleArticleUnfeaturedListener::class,
        ],
        ArticlePinnedEvent::class => [
            HandleArticlePinnedListener::class,
        ],
        ArticleUnpinnedEvent::class => [
            HandleArticleUnpinnedListener::class,
        ],
        ArticleArchivedEvent::class => [
            HandleArticleArchivedListener::class,
        ],
        ArticleRestoredEvent::class => [
            HandleArticleRestoredListener::class,
        ],
        ArticleTrashedEvent::class => [
            HandleArticleTrashedListener::class,
        ],
        ArticleRestoredFromTrashEvent::class => [
            HandleArticleRestoredFromTrashListener::class,
        ],
        ArticleDeletedEvent::class => [
            HandleArticleDeletedListener::class,
        ],
        ArticleReportedEvent::class => [
            HandleArticleReportedListener::class,
        ],
        ArticleReportsClearedEvent::class => [
            HandleArticleReportsClearedListener::class,
        ],

        // Comment Events
        CommentApprovedEvent::class => [
            HandleCommentApprovedListener::class,
        ],
        CommentDeletedEvent::class => [
            HandleCommentDeletedListener::class,
        ],

        // Notification Events
        NotificationCreatedEvent::class => [
            HandleNotificationCreatedListener::class,
        ],
        NotificationSentEvent::class => [
            HandleNotificationSentListener::class,
        ],

        // Newsletter Events
        NewsletterSubscriberCreatedEvent::class => [
            HandleNewsletterSubscriberCreatedListener::class,
        ],
        NewsletterSubscriberDeletedEvent::class => [
            HandleNewsletterSubscriberDeletedListener::class,
        ],
        NewsletterSubscriberUnsubscriptionRequestedEvent::class => [
            HandleNewsletterSubscriberUnsubscriptionRequestedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
