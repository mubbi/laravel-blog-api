<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserUnbannedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/UnbanUserController', function () {
    it('can unban a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnban = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'banned_at',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUnban->id,
            'banned_at' => null,
        ]);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('can unban a user who is also blocked', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnban = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
        expect($userToUnban->blocked_at)->not->toBeNull(); // Should remain blocked
    });

    it('can unban an already unbanned user (no effect)', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnban = User::factory()->create(['banned_at' => null]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 403 when user lacks unban_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $userToUnban = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToUnban = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from unbanning themselves', function () {
        // Arrange
        $admin = User::factory()->create(['banned_at' => now()]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $admin));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_unban_self'),
            ]);

        $admin->refresh();
        expect($admin->banned_at)->not->toBeNull(); // Should remain banned
    });

    it('maintains other user properties when unbanning', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'blocked_at' => now()->subDays(2),
        ];

        $userToUnban = User::factory()->create(array_merge($originalData, [
            'banned_at' => now(),
        ]));

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        $userToUnban->refresh();
        $this->assertNull($userToUnban->banned_at);
        $this->assertEquals($originalData['name'], $userToUnban->name);
        $this->assertEquals($originalData['email'], $userToUnban->email);
        $this->assertEquals($originalData['email_verified_at']->toDateTimeString(), $userToUnban->email_verified_at->toDateTimeString());
        $this->assertEquals($originalData['blocked_at']->toDateTimeString(), $userToUnban->blocked_at->toDateTimeString());
    });

    it('handles users with long ban history', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $oldBanTime = now()->subMonths(6);
        $userToUnban = User::factory()->create(['banned_at' => $oldBanTime]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('dispatches UserUnbannedEvent when user is unbanned', function () {
        // Arrange
        Event::fake([UserUnbannedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnban = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserUnbannedEvent::class, function ($event) use ($userToUnban) {
            return $event->user->id === $userToUnban->id
                && $event->user->banned_at === null;
        });
    });
});
