<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Events\Notification\NotificationCreatedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/Notification/CreateNotificationController', function () {
    it('can create a system notification successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $notificationData = [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'message' => [
                'title' => 'System Maintenance',
                'body' => 'Scheduled maintenance will occur tonight',
                'priority' => 'high',
            ],
            'audiences' => ['all_users'],
        ];

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.notifications.store'), $notificationData);

        expect($response->getStatusCode())->toBe(201)
            ->and($response)->toHaveApiSuccessStructure([
                'id',
                'type',
                'message',
                'created_at',
                'updated_at',
                'audiences',
            ])->and($response->json('message'))->toBe(__('common.notification_created'))
            ->and($response->json('data.type'))->toBe(NotificationType::SYSTEM_ALERT->value)
            ->and($response->json('data.message.title'))->toBe('System Maintenance');

        $this->assertDatabaseHas('notifications', [
            'type' => NotificationType::SYSTEM_ALERT->value,
        ]);
    });

    it('returns 403 when user lacks send_notifications permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $notificationData = [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'message' => ['title' => 'Test'],
            'audiences' => ['all_users'],
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.notifications.store'), $notificationData);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $notificationData = [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'message' => ['title' => 'Test'],
            'audiences' => ['all_users'],
        ];

        // Act
        $response = $this->postJson(route('api.v1.admin.notifications.store'), $notificationData);

        // Assert
        $response->assertStatus(401);
    });

    it('dispatches NotificationCreatedEvent when notification is created', function () {
        // Arrange
        Event::fake([NotificationCreatedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $notificationData = [
            'type' => NotificationType::SYSTEM_ALERT->value,
            'message' => [
                'title' => 'System Maintenance',
                'body' => 'Scheduled maintenance will occur tonight',
                'priority' => 'high',
            ],
            'audiences' => ['all_users'],
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.notifications.store'), $notificationData);

        // Assert
        expect($response)->toHaveApiSuccessStructure()
            ->and($response->getStatusCode())->toBe(201);

        Event::assertDispatched(NotificationCreatedEvent::class, function ($event) {
            return $event->notification->type === NotificationType::SYSTEM_ALERT
                && $event->notification->message['title'] === 'System Maintenance';
        });
    });
});
