<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

describe('API/V1/User/Notification/MarkNotificationAsReadController', function () {
    it('can mark a notification as read', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->unread()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'user_id',
            'notification_id',
            'is_read',
            'created_at',
            'updated_at',
        ])->and($response->json('data.is_read'))->toBeTrue()
            ->and($response->json('data.id'))->toBe($userNotification->id);

        $this->assertDatabaseHas('user_notifications', [
            'id' => $userNotification->id,
            'is_read' => true,
        ]);
    });

    it('can mark an already read notification as read (idempotent)', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->read()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.is_read'))->toBeTrue();
    });

    it('returns 404 when notification does not exist', function () {
        $auth = createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', 99999));

        $response->assertStatus(404);
    });

    it('returns 403 when user tries to mark another user notification as read', function () {
        $auth = createAuthenticatedUser();
        $otherUser = User::factory()->create();

        $notification = Notification::factory()->create();
        $otherUserNotification = UserNotification::factory()->create([
            'user_id' => $otherUser->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', $otherUserNotification));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'notification_id' => $notification->id,
        ]);

        $response = $this->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        $response->assertStatus(401);
    });

    it('returns 403 when user lacks read_notifications permission', function () {
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        $response->assertStatus(403);
    });

    it('updates the updated_at timestamp when marking as read', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->unread()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
            'updated_at' => now()->subDay(),
        ]);

        $originalUpdatedAt = $userNotification->updated_at;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        expect($response->getStatusCode())->toBe(200);
        $userNotification->refresh();
        expect($userNotification->updated_at->timestamp)->toBeGreaterThan($originalUpdatedAt->timestamp);
    });

    it('handles service exception and returns 500', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $this->mock(\App\Services\Interfaces\UserNotificationServiceInterface::class, function ($mock) {
            $mock->shouldReceive('markAsRead')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->postJson(route('api.v1.user.notifications.mark-read', $userNotification));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
