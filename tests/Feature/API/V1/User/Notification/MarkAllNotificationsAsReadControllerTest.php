<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

describe('API/V1/User/Notification/MarkAllNotificationsAsReadController', function () {
    it('can mark all unread notifications as read', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        // Create multiple unread and read notifications
        $notification1 = Notification::factory()->create();
        $notification2 = Notification::factory()->create();
        $notification3 = Notification::factory()->create();

        UserNotification::factory()->unread()->create([
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
        ]);
        UserNotification::factory()->read()->create([
            'user_id' => $user->id,
            'notification_id' => $notification2->id,
        ]);
        UserNotification::factory()->unread()->create([
            'user_id' => $user->id,
            'notification_id' => $notification3->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response)->toHaveApiSuccessStructure([
            'marked_count',
        ])->and($response->json('data.marked_count'))->toBe(2)
            ->and($response->json('message'))->toBe(__('common.all_notifications_marked_as_read'));

        // Verify all notifications are now read
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'notification_id' => $notification1->id,
            'is_read' => true,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $user->id,
            'notification_id' => $notification3->id,
            'is_read' => true,
        ]);
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
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.marked_count'))->toBe(0);
    });

    it('returns zero when user has no notifications', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.marked_count'))->toBe(0);
    });

    it('only marks notifications for the authenticated user', function () {
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
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.marked_count'))->toBe(1);

        // Verify other user's notification is still unread
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $otherUser->id,
            'notification_id' => $notification2->id,
            'is_read' => false,
        ]);
    });

    it('is idempotent - can be called multiple times safely', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        UserNotification::factory()->unread()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        // First call
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response1->json('data.marked_count'))->toBe(1);

        // Second call should return 0 (no unread notifications left)
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response2->json('data.marked_count'))->toBe(0);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson(route('api.v1.user.notifications.mark-all-read'));

        $response->assertStatus(401);
    });

    it('returns 403 when user lacks read_notifications permission', function () {
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        $response->assertStatus(403);
    });

    it('handles service exception and returns 500', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);

        $this->mock(\App\Services\Interfaces\UserNotificationServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAllAsRead')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-all-read'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
