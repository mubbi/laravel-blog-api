<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/BanUserController', function () {
    it('can ban a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userToBan = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $userToBan->id));

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
            'id' => $userToBan->id,
        ]);
        $this->assertNotNull($userToBan->fresh()->banned_at);

        $userToBan->refresh();
        expect($userToBan->banned_at)->not->toBeNull();
        // Status field is handled by the resource, not the model
    });

    it('can ban an already blocked user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userToBan = User::factory()->create(['blocked_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $userToBan->id));

        // Assert
        $response->assertStatus(200);

        $userToBan->refresh();
        expect($userToBan->banned_at)->not->toBeNull();
        expect($userToBan->blocked_at)->not->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 403 when user lacks ban_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($subscriberRole->id);

        $userToBan = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.ban', $userToBan->id));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToBan = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.admin.users.ban', $userToBan->id));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from banning themselves', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $admin->id));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_ban_self'),
            ]);
    });
});
