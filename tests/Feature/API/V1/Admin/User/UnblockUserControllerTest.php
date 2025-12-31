<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/UnblockUserController', function () {
    it('can unblock a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'blocked_at',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUnblock->id,
            'blocked_at' => null,
        ]);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('can unblock a user who is also banned', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnblock = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
        expect($userToUnblock->banned_at)->not->toBeNull(); // Should remain banned
    });

    it('can unblock an already unblocked user (no effect)', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnblock = User::factory()->create(['blocked_at' => null]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 403 when user lacks unblock_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        // Act
        $response = $this->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from unblocking themselves', function () {
        // Arrange
        $admin = User::factory()->create(['blocked_at' => now()]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $admin->id));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_unblock_self'),
            ]);

        $admin->refresh();
        expect($admin->blocked_at)->not->toBeNull(); // Should remain blocked
    });

    it('maintains other user properties when unblocking', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'banned_at' => now()->subDays(2),
        ];

        $userToUnblock = User::factory()->create(array_merge($originalData, [
            'blocked_at' => now(),
        ]));

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200);

        $userToUnblock->refresh();
        $this->assertNull($userToUnblock->blocked_at);
        $this->assertEquals($originalData['name'], $userToUnblock->name);
        $this->assertEquals($originalData['email'], $userToUnblock->email);
        $this->assertEquals($originalData['email_verified_at']->toDateTimeString(), $userToUnblock->email_verified_at->toDateTimeString());
        $this->assertEquals($originalData['banned_at']->toDateTimeString(), $userToUnblock->banned_at->toDateTimeString());
    });

    it('handles users with long block history', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $oldBlockTime = now()->subMonths(3);
        $userToUnblock = User::factory()->create(['blocked_at' => $oldBlockTime]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('can unblock user with recent block', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $recentBlockTime = now()->subHours(2);
        $userToUnblock = User::factory()->create(['blocked_at' => $recentBlockTime]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.unblock', $userToUnblock->id));

        // Assert
        $response->assertStatus(200);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });
});
