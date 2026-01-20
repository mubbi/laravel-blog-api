<?php

declare(strict_types=1);

use App\Data\Notification\CreateNotificationDTO;
use App\Data\Notification\FilterNotificationDTO;
use App\Enums\NotificationType;
use App\Events\Notification\NotificationCreatedEvent;
use App\Events\Notification\NotificationSentEvent;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Event;

describe('NotificationService', function () {
    beforeEach(function () {
        $this->service = app(NotificationService::class);
    });

    describe('createNotification', function () {
        it('creates a notification successfully', function () {
            // Arrange
            Event::fake();
            $dto = new CreateNotificationDTO(
                type: NotificationType::ARTICLE_PUBLISHED,
                message: ['title' => 'Test', 'body' => 'Test body'],
                audiences: ['all_users']
            );

            // Act
            $result = $this->service->createNotification($dto);

            // Assert
            expect($result)->toBeInstanceOf(Notification::class);
            expect($result->type)->toBe(NotificationType::ARTICLE_PUBLISHED);
            expect($result->message)->toBe(['title' => 'Test', 'body' => 'Test body']);
            Event::assertDispatched(NotificationCreatedEvent::class);
        });

        it('creates notification with specific users audience', function () {
            // Arrange
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $dto = new CreateNotificationDTO(
                type: NotificationType::NEW_COMMENT,
                message: ['title' => 'Test'],
                audiences: ['specific_users'],
                userIds: [$user1->id, $user2->id]
            );

            // Act
            $result = $this->service->createNotification($dto);

            // Assert
            expect($result->audiences)->toHaveCount(2);
            expect($result->audiences->pluck('audience_id')->toArray())->toContain($user1->id, $user2->id);
        });

        it('creates notification with administrators audience', function () {
            // Arrange
            $adminRole = Role::firstOrCreate(
                ['name' => 'administrator'],
                ['slug' => 'administrator']
            );
            $dto = new CreateNotificationDTO(
                type: NotificationType::SYSTEM_ALERT,
                message: ['title' => 'Alert'],
                audiences: ['administrators']
            );

            // Act
            $result = $this->service->createNotification($dto);

            // Assert
            expect($result->audiences)->toHaveCount(1);
            expect($result->audiences->first()->audience_type)->toBe('role');
            expect($result->audiences->first()->audience_id)->toBe($adminRole->id);
        });

        it('creates notification with all_users audience', function () {
            // Arrange
            $dto = new CreateNotificationDTO(
                type: NotificationType::NEWSLETTER,
                message: ['title' => 'Newsletter'],
                audiences: ['all_users']
            );

            // Act
            $result = $this->service->createNotification($dto);

            // Assert
            expect($result->audiences)->toHaveCount(1);
            expect($result->audiences->first()->audience_type)->toBe('all');
            expect($result->audiences->first()->audience_id)->toBeNull();
        });
    });

    describe('sendNotification', function () {
        it('sends a notification successfully', function () {
            // Arrange
            Event::fake();
            $notification = Notification::factory()->create();

            // Act
            $this->service->sendNotification($notification);

            // Assert
            Event::assertDispatched(NotificationSentEvent::class);
        });
    });

    describe('getNotificationById', function () {
        it('can get notification by id with relationships', function () {
            // Arrange
            $notification = Notification::factory()->create();
            $notification->audiences()->create([
                'audience_type' => 'all',
                'audience_id' => null,
            ]);

            // Act
            $result = $this->service->getNotificationById($notification->id);

            // Assert
            expect($result->id)->toBe($notification->id);
            expect($result->relationLoaded('audiences'))->toBeTrue();
        });

        it('throws ModelNotFoundException when notification does not exist', function () {
            // Act & Assert
            expect(fn () => $this->service->getNotificationById(99999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getNotifications', function () {
        it('can get paginated notifications', function () {
            // Arrange
            Notification::factory()->count(20)->create();

            $dto = new FilterNotificationDTO(
                perPage: 10
            );

            // Act
            $result = $this->service->getNotifications($dto);

            // Assert
            expect($result->count())->toBe(10);
            expect($result->total())->toBe(20);
        });

        it('can filter notifications by type', function () {
            // Arrange
            Notification::factory()->count(5)->create(['type' => NotificationType::ARTICLE_PUBLISHED]);
            Notification::factory()->count(3)->create(['type' => NotificationType::NEW_COMMENT]);

            $dto = new FilterNotificationDTO(
                type: NotificationType::ARTICLE_PUBLISHED
            );

            // Act
            $result = $this->service->getNotifications($dto);

            // Assert
            expect($result->total())->toBe(5);
        });

        it('can filter notifications by date range', function () {
            // Arrange
            $oldNotification = Notification::factory()->create(['created_at' => now()->subDays(10)->startOfDay()]);
            $inRangeNotification1 = Notification::factory()->create(['created_at' => now()->subDays(5)->startOfDay()]);
            $inRangeNotification2 = Notification::factory()->create(['created_at' => now()->subDays(1)->startOfDay()]);

            // Filter from 7 days ago to tomorrow to ensure we include all notifications created at start of day
            // MySQL compares date strings as datetime at 00:00:00, so we need to account for that
            $dto = new FilterNotificationDTO(
                createdAtFrom: now()->subDays(7)->startOfDay()->toDateString(),
                createdAtTo: now()->addDay()->startOfDay()->toDateString()
            );

            // Act
            $result = $this->service->getNotifications($dto);

            // Assert
            expect($result->total())->toBe(2);
            expect(collect($result->items())->pluck('id')->toArray())->toContain($inRangeNotification1->id, $inRangeNotification2->id);
        });
    });

    describe('getTotalNotifications', function () {
        it('returns total notification count', function () {
            // Arrange
            Notification::factory()->count(5)->create();

            // Act
            $result = $this->service->getTotalNotifications();

            // Assert
            expect($result)->toBe(5);
        });
    });

    describe('getNotificationStats', function () {
        it('returns notification statistics', function () {
            // Arrange
            Notification::factory()->count(3)->create(['type' => NotificationType::ARTICLE_PUBLISHED]);
            Notification::factory()->count(2)->create(['type' => NotificationType::NEW_COMMENT]);
            Notification::factory()->count(1)->create(['type' => NotificationType::NEWSLETTER]);

            // Act
            $result = $this->service->getNotificationStats();

            // Assert
            expect($result['total'])->toBe(6);
            expect($result['by_type']['article_published'])->toBe(3);
            expect($result['by_type']['new_comment'])->toBe(2);
            expect($result['by_type']['newsletter'])->toBe(1);
        });
    });
});
