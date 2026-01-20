<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;

describe('API/V1/User/Notification/DeleteNotificationController', function () {
    it('can delete a user notification', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.user.notifications.destroy', $userNotification));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('status'))->toBeTrue()
            ->and($response->json('data'))->toBeNull()
            ->and($response->json('message'))->toBe(__('common.notification_deleted_successfully'));

        $this->assertDatabaseMissing('user_notifications', [
            'id' => $userNotification->id,
        ]);
    });

    it('returns 404 when notification does not exist', function () {
        $auth = createAuthenticatedUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.user.notifications.destroy', 99999));

        $response->assertStatus(404);
    });

    it('returns 403 when user tries to delete another user notification', function () {
        $auth = createAuthenticatedUser();
        $otherUser = User::factory()->create();

        $notification = Notification::factory()->create();
        $otherUserNotification = UserNotification::factory()->create([
            'user_id' => $otherUser->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.user.notifications.destroy', $otherUserNotification));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'notification_id' => $notification->id,
        ]);

        $response = $this->deleteJson(route('api.v1.user.notifications.destroy', $userNotification));

        $response->assertStatus(401);
    });

    it('returns 403 when user lacks delete_notifications permission', function () {
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
        ])->deleteJson(route('api.v1.user.notifications.destroy', $userNotification));

        $response->assertStatus(403);
    });

    it('does not delete the underlying notification when deleting user notification', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::AUTHOR->value);
        $user = $auth['user'];

        $notification = Notification::factory()->create();
        $userNotification = UserNotification::factory()->create([
            'user_id' => $user->id,
            'notification_id' => $notification->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.user.notifications.destroy', $userNotification));

        expect($response->getStatusCode())->toBe(200);

        // Verify the notification itself still exists
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        // But the user notification is deleted
        $this->assertDatabaseMissing('user_notifications', [
            'id' => $userNotification->id,
        ]);
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
            $mock->shouldReceive('deleteNotification')
                ->andThrow(new \Exception('Service error'));
        });

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->deleteJson(route('api.v1.user.notifications.destroy', $userNotification));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
