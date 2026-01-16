<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

describe('API/V1/User/Notification/GetUnreadNotificationsCountController', function () {
    it('can get unread notifications count', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        // Create read and unread notifications
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
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        expect($response)->toHaveApiSuccessStructure([
            'unread_count',
        ])->and($response->json('data.unread_count'))->toBe(2);
    });

    it('returns zero when user has no unread notifications', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        UserNotification::factory()->read()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.unread_count'))->toBe(0);
    });

    it('returns zero when user has no notifications', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.unread_count'))->toBe(0);
    });

    it('only counts unread notifications for the authenticated user', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $otherUser = User::factory()->create();

        $notification1 = Notification::factory()->create();
        $notification2 = Notification::factory()->create();

        // Create unread notification for authenticated user
        UserNotification::factory()->unread()->create([
            'user_id' => $auth['user']->id,
            'notification_id' => $notification1->id,
        ]);

        // Create unread notification for other user
        UserNotification::factory()->unread()->create([
            'user_id' => $otherUser->id,
            'notification_id' => $notification2->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.unread_count'))->toBe(1);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson(route('api.v1.user.notifications.unread-count'));

        $response->assertStatus(401);
    });

    it('returns 403 when user lacks read_notifications permission', function () {
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        $response->assertStatus(403);
    });

    it('handles service exception and returns 500', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $this->mock(\App\Services\Interfaces\UserNotificationServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getUnreadCount')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.user.notifications.unread-count'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
