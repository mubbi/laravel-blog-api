<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/DeleteUserController', function () {
    it('can delete a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('common.user_deleted_successfully'),
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('can delete a banned user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create(['banned_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('can delete a blocked user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create(['blocked_at' => now()]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('can delete a user with both banned and blocked status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create([
            'banned_at' => now()->subDays(5),
            'blocked_at' => now()->subDays(2),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 403 when user lacks delete_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $userToDelete = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToDelete = User::factory()->create();

        // Act
        $response = $this->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from deleting themselves', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $admin->id));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_delete_self'),
            ]);
    });

    it('deletes user with verified email', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('deletes user with unverified email', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('deletes user with roles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($userToDelete, $authorRole);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('deletes user with multiple roles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $editorRole = Role::where('name', UserRole::EDITOR->value)->first();
        $userToDelete->roles()->attach([$authorRole->id, $editorRole->id]);

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });

    it('deletes user with no roles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToDelete = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->deleteJson(route('api.v1.admin.users.destroy', $userToDelete->id));

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    });
});
