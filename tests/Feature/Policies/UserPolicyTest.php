<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserPolicy', function () {
    beforeEach(function () {
        $this->policy = new UserPolicy;
    });

    describe('view', function () {
        it('allows user with view_users permission to view user', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'view_users'], ['slug' => 'view_users']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $targetUser = User::factory()->create();

            // Act
            $result = $this->policy->view($user, $targetUser);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without view_users permission to view user', function () {
            // Arrange
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            // Act
            $result = $this->policy->view($user, $targetUser);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with create_users permission to create user', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'create_users'], ['slug' => 'create_users']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without create_users permission to create user', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with edit_users permission to update any user', function () {
            // Arrange
            $user = User::factory()->create();
            $targetUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'edit_users'], ['slug' => 'edit_users']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            // Act
            $result = $this->policy->update($user, $targetUser);

            // Assert
            expect($result)->toBeTrue();
        });

        it('allows user to update own profile', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->update($user, $user);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without edit_users permission to update other user', function () {
            // Arrange
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            // Act
            $result = $this->policy->update($user, $targetUser);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with delete_users permission to delete user', function () {
            // Arrange
            $user = User::factory()->create();
            $targetUser = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'delete_users'], ['slug' => 'delete_users']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            // Act
            $result = $this->policy->delete($user, $targetUser);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without delete_users permission to delete user', function () {
            // Arrange
            $user = User::factory()->create();
            $targetUser = User::factory()->create();

            // Act
            $result = $this->policy->delete($user, $targetUser);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
