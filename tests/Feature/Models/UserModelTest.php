<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Models\Article;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

describe('User Model', function () {
    describe('hasRole', function () {
        it('returns true when user has the role', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasRole($role->name);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user does not have the role', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $otherRole = Role::factory()->create();
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasRole($otherRole->name);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when user has no roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();

            // Act
            $result = $user->hasRole($role->name);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('hasAnyRole', function () {
        it('returns true when user has at least one of the roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();
            $user->roles()->attach($role1->id);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasAnyRole([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user has none of the roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasAnyRole([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when user has no roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();

            // Act
            $result = $user->hasAnyRole([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('hasAllRoles', function () {
        it('returns true when user has all of the roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();
            $user->roles()->attach([$role1->id, $role2->id]);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasAllRoles([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user has only some of the roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();
            $user->roles()->attach($role1->id);
            $user->refresh();
            $user->load('roles');

            // Act
            $result = $user->hasAllRoles([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when user has no roles', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();

            // Act
            $result = $user->hasAllRoles([$role1->name, $role2->name]);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('hasPermission', function () {
        it('returns true when user has permission via role', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasPermission('edit_posts');

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user does not have permission', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $otherPermission = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasPermission('delete_posts');

            // Assert
            expect($result)->toBeFalse();
        });

        it('returns false when user has no roles', function () {
            // Arrange
            $user = User::factory()->create();

            // Act
            $result = $user->hasPermission('edit_posts');

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('hasAnyPermission', function () {
        it('returns true when user has at least one permission', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission1 = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $permission2 = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission1->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasAnyPermission(['edit_posts', 'delete_posts']);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user has none of the permissions', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission = Permission::firstOrCreate(
                ['name' => 'view_posts'],
                ['slug' => 'view_posts']
            );
            $permission1 = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $permission2 = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasAnyPermission(['edit_posts', 'delete_posts']);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('hasAllPermissions', function () {
        it('returns true when user has all permissions', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission1 = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $permission2 = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach([$permission1->id, $permission2->id]);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasAllPermissions(['edit_posts', 'delete_posts']);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false when user has only some permissions', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $permission1 = Permission::firstOrCreate(
                ['name' => 'edit_posts'],
                ['slug' => 'edit_posts']
            );
            $permission2 = Permission::firstOrCreate(
                ['name' => 'delete_posts'],
                ['slug' => 'delete_posts']
            );
            $role->permissions()->attach($permission1->id);
            $user->roles()->attach($role->id);
            $user->refresh();
            $user->load('roles.permissions');
            $user->clearCache();

            // Act
            $result = $user->hasAllPermissions(['edit_posts', 'delete_posts']);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('relationships', function () {
        it('has articles relationship', function () {
            // Arrange
            $user = User::factory()->create();
            Article::factory()->count(3)->create(['created_by' => $user->id]);

            // Act
            $articles = $user->articles;

            // Assert
            expect($articles)->toHaveCount(3);
        });

        it('has roles relationship', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();
            $user->roles()->attach([$role1->id, $role2->id]);

            // Act
            $roles = $user->roles;

            // Assert
            expect($roles)->toHaveCount(2);
        });
    });

    describe('clearCache', function () {
        it('clears user cache', function () {
            // Arrange
            $user = User::factory()->create();
            $cacheVersion = Cache::get('user_cache_version', 1);
            $cacheKey = CacheKeys::userPermissions($user->id).'_v'.$cacheVersion;
            Cache::put($cacheKey, ['edit_posts'], 3600);
            $user->clearCache();

            // Act & Assert
            expect(Cache::has($cacheKey))->toBeFalse();
        });
    });
});
