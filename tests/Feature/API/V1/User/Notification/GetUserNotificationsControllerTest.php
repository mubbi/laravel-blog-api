<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

describe('API/V1/User/Notification/GetUserNotificationsController', function () {
    it('can get paginated list of user notifications', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        // Create notifications for the user
        $notification1 = Notification::factory()->create();
        $notification2 = Notification::factory()->create();
        $notification3 = Notification::factory()->create();

        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
            'is_read' => false,
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification2->id,
            'is_read' => true,
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification3->id,
            'is_read' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index'));

        expect($response)->toHaveApiSuccessStructure([
            'notifications' => [
                '*' => [
                    'id',
                    'user_id',
                    'notification_id',
                    'is_read',
                    'created_at',
                    'updated_at',
                    'notification' => [
                        'id',
                        'type',
                        'message',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ])->and($response->json('data.notifications'))->toBeArray()
            ->and(count($response->json('data.notifications')))->toBe(3);
    });

    it('can filter notifications by read status', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification1 = Notification::factory()->create();
        $notification2 = Notification::factory()->create();

        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
            'is_read' => false,
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification2->id,
            'is_read' => true,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index', ['is_read' => false]));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        expect($notifications)->toHaveCount(1)
            ->and($notifications[0]['is_read'])->toBeFalse();
    });

    it('can filter notifications by type', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification1 = Notification::factory()->create(['type' => NotificationType::ARTICLE_PUBLISHED]);
        $notification2 = Notification::factory()->create(['type' => NotificationType::NEW_COMMENT]);

        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification2->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index', ['type' => NotificationType::ARTICLE_PUBLISHED->value]));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        expect($notifications)->toHaveCount(1)
            ->and($notifications[0]['notification']['type'])->toBe(NotificationType::ARTICLE_PUBLISHED->value);
    });

    it('can filter notifications by date range', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $oldNotification = Notification::factory()->create(['created_at' => now()->subDays(10)]);
        $recentNotification = Notification::factory()->create(['created_at' => now()->subDays(2)]);

        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $oldNotification->id,
            'created_at' => now()->subDays(10),
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $recentNotification->id,
            'created_at' => now()->subDays(2),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index', [
            'created_at_from' => now()->subDays(5)->toDateString(),
            'created_at_to' => now()->toDateString(),
        ]));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        expect($notifications)->toHaveCount(1)
            ->and($notifications[0]['notification_id'])->toBe($recentNotification->id);
    });

    it('can sort notifications by created_at descending', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification1 = Notification::factory()->create(['created_at' => now()->subDays(3)]);
        $notification2 = Notification::factory()->create(['created_at' => now()->subDays(1)]);
        $notification3 = Notification::factory()->create(['created_at' => now()->subDays(5)]);

        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
            'created_at' => now()->subDays(3),
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification2->id,
            'created_at' => now()->subDays(1),
        ]);
        UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification3->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index', [
            'sort_by' => 'created_at',
            'sort_order' => 'desc',
        ]));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        $createdAts = collect($notifications)->pluck('created_at')->toArray();
        expect($createdAts[0])->toBeGreaterThan($createdAts[1])
            ->and($createdAts[1])->toBeGreaterThan($createdAts[2]);
    });

    it('can paginate notifications', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        // Create 25 notifications
        for ($i = 0; $i < 25; $i++) {
            $notification = Notification::factory()->create();
            UserNotification::factory()->create([
                'user_id' => $user->id,
                'notification_id' => $notification->id,
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index', [
            'per_page' => 10,
            'page' => 2,
        ]));

        expect($response->getStatusCode())->toBe(200);
        $meta = $response->json('data.meta');
        expect($meta['current_page'])->toBe(2)
            ->and($meta['per_page'])->toBe(10)
            ->and($meta['total'])->toBeGreaterThanOrEqual(25);
    });

    it('only returns notifications for the authenticated user', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $otherUser = User::factory()->create();

        $notification1 = Notification::factory()->create();
        $notification2 = Notification::factory()->create();

        // Create notification for authenticated user
        UserNotification::factory()->create([
            'user_id' => $auth['user']->id,
            'notification_id' => $notification1->id,
        ]);

        // Create notification for other user
        UserNotification::factory()->create([
            'user_id' => $otherUser->id,
            'notification_id' => $notification2->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index'));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        expect($notifications)->toHaveCount(1)
            ->and($notifications[0]['user_id'])->toBe($auth['user']->id);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson(route('api.v1.user.notifications.index'));

        $response->assertStatus(401);
    });

    it('returns 403 when user lacks read_notifications permission', function () {
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.user.notifications.index'));

        $response->assertStatus(403);
    });

    it('handles empty results gracefully', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index'));

        expect($response->getStatusCode())->toBe(200);
        $notifications = $response->json('data.notifications');
        expect($notifications)->toBeArray()
            ->and($notifications)->toHaveCount(0);
    });

    it('handles service exception and returns 500', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $this->mock(\App\Services\Interfaces\UserNotificationServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getUserNotifications')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.index'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
