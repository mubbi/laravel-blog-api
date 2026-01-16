<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use App\Policies\TagPolicy;

describe('TagPolicy', function () {
    beforeEach(function () {
        $this->policy = new TagPolicy;
    });

    describe('view', function () {
        it('allows user with manage_tags permission to view tag', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_tags'], ['slug' => 'manage_tags']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->view($user, $tag);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_tags permission to view tag', function () {
            // Arrange
            $user = User::factory()->create();
            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->view($user, $tag);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with manage_tags permission to create tag', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_tags'], ['slug' => 'manage_tags']);
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

        it('denies user without manage_tags permission to create tag', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with manage_tags permission to update tag', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_tags'], ['slug' => 'manage_tags']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->update($user, $tag);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_tags permission to update tag', function () {
            // Arrange
            $user = User::factory()->create();
            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->update($user, $tag);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with manage_tags permission to delete tag', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(['name' => 'manage_tags'], ['slug' => 'manage_tags']);
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->delete($user, $tag);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_tags permission to delete tag', function () {
            // Arrange
            $user = User::factory()->create();
            $tag = Tag::factory()->create();

            // Act
            $result = $this->policy->delete($user, $tag);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
