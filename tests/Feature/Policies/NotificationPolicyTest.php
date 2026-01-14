<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\NotificationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('NotificationPolicy', function () {
    beforeEach(function () {
        $this->policy = new NotificationPolicy;
    });

    describe('view', function () {
        it('allows user with view_notifications permission to view notification', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'view_notifications'], ['slug' => 'view_notifications']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->view($user, $notification);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without view_notifications permission to view notification', function () {
            // Arrange
            $user = User::factory()->create();
            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->view($user, $notification);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with send_notifications permission to create notification', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'send_notifications'], ['slug' => 'send_notifications']);
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

        it('denies user without send_notifications permission to create notification', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with manage_notifications permission to update notification', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_notifications'], ['slug' => 'manage_notifications']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->update($user, $notification);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_notifications permission to update notification', function () {
            // Arrange
            $user = User::factory()->create();
            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->update($user, $notification);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with manage_notifications permission to delete notification', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_notifications'], ['slug' => 'manage_notifications']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->delete($user, $notification);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_notifications permission to delete notification', function () {
            // Arrange
            $user = User::factory()->create();
            $notification = Notification::factory()->create();

            // Act
            $result = $this->policy->delete($user, $notification);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
