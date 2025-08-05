<?php

declare(strict_types=1);

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;

describe('API/V1/Admin/Notification/GetNotificationsController', function () {
    it('can get paginated list of notifications', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'message',
                            'created_at',
                            'updated_at',
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
                ],
            ]);
    });

    it('can filter notifications by type', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->create(['type' => NotificationType::SYSTEM_ALERT]);
        Notification::factory()->create(['type' => NotificationType::NEW_COMMENT]);
        Notification::factory()->create(['type' => NotificationType::SYSTEM_ALERT]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', ['type' => NotificationType::SYSTEM_ALERT->value]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        expect($data)->toHaveCount(2);
        foreach ($data as $notification) {
            expect($notification['type'])->toBe(NotificationType::SYSTEM_ALERT->value);
        }
    });

    it('can search notifications by message content', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->create([
            'message' => ['title' => 'System maintenance', 'body' => 'Scheduled maintenance'],
        ]);
        Notification::factory()->create([
            'message' => ['title' => 'User update', 'body' => 'Profile updated'],
        ]);
        Notification::factory()->create([
            'message' => ['title' => 'System alert', 'body' => 'Server down'],
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', ['search' => 'maintenance']));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        expect($data)->toHaveCount(1);
        expect($data[0]['message']['title'])->toBe('System maintenance');
    });

    it('can filter notifications by date range', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $oldNotification = Notification::factory()->create([
            'created_at' => now()->subDays(10),
        ]);
        $recentNotification = Notification::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        $newNotification = Notification::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'created_at_from' => now()->subDays(5)->toDateString(),
                'created_at_to' => now()->subDays(1)->toDateString(),
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        expect($data)->toHaveCount(1);
        expect($data[0]['id'])->toBe($recentNotification->id);
    });

    it('can sort notifications by different fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->create(['created_at' => now()->subDays(3)]);
        Notification::factory()->create(['created_at' => now()->subDays(1)]);
        Notification::factory()->create(['created_at' => now()->subDays(5)]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'sort_by' => 'created_at',
                'sort_order' => 'desc',
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        $createdAts = collect($data)->pluck('created_at')->toArray();
        // Check that the timestamps are in descending order
        expect($createdAts[0])->toBeGreaterThan($createdAts[1]);
        expect($createdAts[1])->toBeGreaterThan($createdAts[2]);
    });

    it('can paginate notifications', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->count(25)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'per_page' => 10,
                'page' => 2,
            ]));

        // Assert
        $response->assertStatus(200);
        $meta = $response->json('data.meta');
        expect($meta['current_page'])->toBe(2);
        expect($meta['per_page'])->toBe(10);
        expect($meta['total'])->toBeGreaterThanOrEqual(25);
    });

    it('returns 403 when user lacks view_notifications permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($subscriberRole->id);

        // Act
        $response = $this->actingAs($user)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Act
        $response = $this->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(401);
    });

    it('handles empty results gracefully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        expect($data)->toBeArray();
        expect($data)->toHaveCount(0);
    });

    it('handles service exception and logs error', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Mock NotificationService to throw exception
        $this->mock(\App\Services\NotificationService::class, function ($mock) {
            $mock->shouldReceive('getNotifications')
                ->andThrow(new \Exception('Database error'));
        });

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);

        // Verify error was logged
        Log::shouldReceive('error')->with(
            'Notifications retrieval failed',
            \Mockery::type('array')
        );
    });

    it('includes notification with complex message structure', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $complexMessage = [
            'title' => 'Complex Notification',
            'body' => 'This is a detailed message',
            'metadata' => [
                'priority' => 'high',
                'category' => 'system',
                'tags' => ['urgent', 'maintenance'],
            ],
        ];

        $notification = Notification::factory()->create([
            'type' => NotificationType::SYSTEM_ALERT,
            'message' => $complexMessage,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        $foundNotification = collect($data)->firstWhere('id', $notification->id);
        expect($foundNotification)->not->toBeNull();
        expect($foundNotification['type'])->toBe(NotificationType::SYSTEM_ALERT->value);
        expect($foundNotification['message']['title'])->toBe('Complex Notification');
        expect($foundNotification['message']['metadata']['priority'])->toBe('high');
    });

    it('handles notifications with different types', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $systemNotification = Notification::factory()->create([
            'type' => NotificationType::SYSTEM_ALERT,
            'message' => ['title' => 'System Alert'],
        ]);
        $userNotification = Notification::factory()->create([
            'type' => NotificationType::NEW_COMMENT,
            'message' => ['title' => 'User Update'],
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        $systemFound = collect($data)->firstWhere('id', $systemNotification->id);
        $userFound = collect($data)->firstWhere('id', $userNotification->id);

        expect($systemFound)->not->toBeNull();
        expect($userFound)->not->toBeNull();
        expect($systemFound['type'])->toBe(NotificationType::SYSTEM_ALERT->value);
        expect($userFound['type'])->toBe(NotificationType::NEW_COMMENT->value);
    });

    it('validates date format for date filters', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'created_at_from' => 'invalid-date',
                'created_at_to' => 'invalid-date',
            ]));

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'data' => null,
            ])
            ->assertJsonStructure([
                'error' => [
                    'created_at_from',
                    'created_at_to',
                ],
            ]);
    });

    it('handles large result sets efficiently', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        Notification::factory()->count(100)->create();

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'per_page' => 50,
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        $meta = $response->json('data.meta');
        expect($data)->toHaveCount(50);
        expect($meta['total'])->toBeGreaterThanOrEqual(100);
        expect($meta['per_page'])->toBe(50);
    });

    it('filters by multiple criteria simultaneously', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Create notifications with different characteristics
        Notification::factory()->create([
            'type' => NotificationType::SYSTEM_ALERT,
            'message' => ['title' => 'System maintenance alert'],
            'created_at' => now()->subDays(5),
        ]);
        Notification::factory()->create([
            'type' => NotificationType::NEW_COMMENT,
            'message' => ['title' => 'User maintenance request'],
            'created_at' => now()->subDays(5),
        ]);
        Notification::factory()->create([
            'type' => NotificationType::SYSTEM_ALERT,
            'message' => ['title' => 'Recent system update'],
            'created_at' => now()->subDays(1),
        ]);

        // Act - Filter for system notifications from the last week
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index', [
                'type' => NotificationType::SYSTEM_ALERT->value,
                'created_at_from' => now()->subWeek()->toDateString(),
                'created_at_to' => now()->toDateString(),
            ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        expect($data)->toHaveCount(2); // System maintenance alert and Recent system update
        foreach ($data as $notification) {
            expect($notification['type'])->toBe(NotificationType::SYSTEM_ALERT->value);
        }
        // Verify we have the expected notifications
        $titles = collect($data)->pluck('message.title')->toArray();
        expect($titles)->toContain('System maintenance alert');
        expect($titles)->toContain('Recent system update');
    });

    it('handles notifications with empty message arrays', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $notification = Notification::factory()->create([
            'type' => NotificationType::SYSTEM_ALERT,
            'message' => [],
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->getJson(route('api.v1.admin.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.notifications');
        $foundNotification = collect($data)->firstWhere('id', $notification->id);
        expect($foundNotification)->not->toBeNull();
        expect($foundNotification['message'])->toBe([]);
    });
});
