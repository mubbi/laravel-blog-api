<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CategoryPolicy', function () {
    beforeEach(function () {
        $this->policy = new CategoryPolicy;
    });

    describe('view', function () {
        it('allows user with manage_categories permission to view category', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_categories'],
                ['slug' => 'manage_categories']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $category = Category::factory()->create();

            // Act
            $result = $this->policy->view($user, $category);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_categories permission to view category', function () {
            // Arrange
            $user = User::factory()->create();
            $category = Category::factory()->create();

            // Act
            $result = $this->policy->view($user, $category);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows user with manage_categories permission to create category', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_categories'],
                ['slug' => 'manage_categories']
            );
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

        it('denies user without manage_categories permission to create category', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $this->policy->create($user);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows user with manage_categories permission to update category', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_categories'],
                ['slug' => 'manage_categories']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $category = Category::factory()->create();

            // Act
            $result = $this->policy->update($user, $category);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_categories permission to update category', function () {
            // Arrange
            $user = User::factory()->create();
            $category = Category::factory()->create();

            // Act
            $result = $this->policy->update($user, $category);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows user with manage_categories permission to delete category', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'manage_categories'],
                ['slug' => 'manage_categories']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->refreshCache();

            $category = Category::factory()->create();

            // Act
            $result = $this->policy->delete($user, $category);

            // Assert
            expect($result)->toBeTrue();
        });

        it('denies user without manage_categories permission to delete category', function () {
            // Arrange
            $user = User::factory()->create();
            $category = Category::factory()->create();

            // Act
            $result = $this->policy->delete($user, $category);

            // Assert
            expect($result)->toBeFalse();
        });
    });
});
