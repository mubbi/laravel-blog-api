<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/Notification/CreateNotificationController', function () {
    it('can create a system notification successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

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
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'type',
                    'message',
                    'created_at',
                    'updated_at',
                    'audiences',
                ],
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.notification_created'),
                'data' => [
                    'type' => NotificationType::SYSTEM_ALERT->value,
                    'message' => [
                        'title' => 'System Maintenance',
                        'body' => 'Scheduled maintenance will occur tonight',
                        'priority' => 'high',
                    ],
                ],
            ]);

        // Verify notification was created in database
        $this->assertDatabaseHas('notifications', [
            'type' => NotificationType::SYSTEM_ALERT->value,
        ]);
    });

    it('returns 403 when user lacks send_notifications permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($subscriberRole->id);

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
});
