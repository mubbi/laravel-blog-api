<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserBlockedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/BlockUserController', function () {
    it('can block a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToBlock = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', $userToBlock));

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
            'id' => $userToBlock->id,
        ]);
        $this->assertNotNull($userToBlock->fresh()->blocked_at);

        $userToBlock->refresh();
        expect($userToBlock->blocked_at)->not->toBeNull();
    });

    it('can block an already banned user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToBlock = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', $userToBlock));

        // Assert
        $response->assertStatus(200);

        $userToBlock->refresh();
        expect($userToBlock->blocked_at)->not->toBeNull();
        expect($userToBlock->banned_at)->not->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 403 when user lacks block_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $userToBlock = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.block', $userToBlock));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToBlock = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.admin.users.block', $userToBlock));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from blocking themselves', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', $admin));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_block_self'),
            ]);
    });

    it('maintains other user properties when blocking', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'banned_at' => null,
        ];

        $userToBlock = User::factory()->create($originalData);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', $userToBlock));

        // Assert
        $response->assertStatus(200);

        $userToBlock->refresh();
        $this->assertNotNull($userToBlock->blocked_at);
        $this->assertEquals($originalData['name'], $userToBlock->name);
        $this->assertEquals($originalData['email'], $userToBlock->email);
        $this->assertEquals($originalData['email_verified_at']->toDateTimeString(), $userToBlock->email_verified_at->toDateTimeString());
        $this->assertEquals($originalData['banned_at'], $userToBlock->banned_at);
    });

    it('dispatches UserBlockedEvent when user is blocked', function () {
        // Arrange
        Event::fake([UserBlockedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToBlock = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.block', $userToBlock));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserBlockedEvent::class, function ($event) use ($userToBlock) {
            return $event->user->id === $userToBlock->id
                && $event->user->blocked_at !== null;
        });
    });
});
