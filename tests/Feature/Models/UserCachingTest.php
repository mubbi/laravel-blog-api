<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;

describe('User Caching', function () {
    beforeEach(function () {
        // Restore event dispatcher for Eloquent models to allow model events to run
        // This test suite depends on model event callbacks (Role::boot(), User::boot())
        // to properly increment cache versions when roles/users are updated
        // Event::fake() in TestCase prevents all events, including model events
        // So we need to set a real dispatcher for models specifically
        Model::setEventDispatcher(new Dispatcher);

        // Clear all caches before each test
        Cache::flush();
    });

    it('caches user roles correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $cachedRoles = $user->getCachedRoles();

        // Assert
        expect($cachedRoles)->toBe([UserRole::AUTHOR->value]);
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
    });

    it('caches user permissions correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $permission = Permission::where('name', 'publish_posts')->first();

        // Check if permission is already attached to avoid duplicates
        if (! $role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        // Act
        $cachedPermissions = $user->getCachedPermissions();

        // Assert
        expect($cachedPermissions)->toContain('publish_posts');
        expect(Cache::has('user_permissions_'.$user->id.'_v1'))->toBeTrue();
    });

    it('uses cached data for permission checks', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $permission = Permission::where('name', 'publish_posts')->first();

        // Check if permission is already attached to avoid duplicates
        if (! $role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        // Act - First call should cache
        $hasPermission = $user->hasCachedPermission('publish_posts');

        // Assert
        expect($hasPermission)->toBeTrue();
        expect(Cache::has('user_permissions_'.$user->id.'_v1'))->toBeTrue();
    });

    it('clears cache when user is updated', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache some data
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act
        $user->update(['name' => 'Updated Name']);

        // Assert - Cache should be cleared
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeFalse();
    });

    it('invalidates all caches when role permissions change', function () {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();

        $user1->roles()->attach($role->id);
        $user2->roles()->attach($role->id);

        // Cache data for both users
        $user1->getCachedRoles();
        $user2->getCachedRoles();

        expect(Cache::has('user_roles_'.$user1->id.'_v1'))->toBeTrue();
        expect(Cache::has('user_roles_'.$user2->id.'_v1'))->toBeTrue();

        // Act - Update role (this should increment cache version)
        $role->update(['name' => 'Updated Author']);

        // Assert - New cache should be created with new version
        $user1->getCachedRoles();
        expect(Cache::has('user_roles_'.$user1->id.'_v2'))->toBeTrue();
    });

    it('handles multiple permissions correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $permission1 = Permission::where('name', 'publish_posts')->first();
        $permission2 = Permission::where('name', 'edit_posts')->first();

        // Check if permissions are already attached to avoid duplicates
        if (! $role->permissions()->where('permission_id', $permission1->id)->exists()) {
            $role->permissions()->attach($permission1->id);
        }
        if (! $role->permissions()->where('permission_id', $permission2->id)->exists()) {
            $role->permissions()->attach($permission2->id);
        }
        $user->roles()->attach($role->id);

        // Act
        $hasAny = $user->hasAnyCachedPermission(['publish_posts', 'delete_posts']);
        $hasAll = $user->hasAllCachedPermissions(['publish_posts', 'edit_posts']);

        // Assert
        expect($hasAny)->toBeTrue(); // Has publish_posts
        expect($hasAll)->toBeTrue(); // Has both permissions
    });

    it('handles multiple roles correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $editorRole = Role::where('name', UserRole::EDITOR->value)->first();
        $user->roles()->attach([$authorRole->id, $editorRole->id]);

        // Act
        $hasAny = $user->hasAnyCachedRole([UserRole::AUTHOR->value, UserRole::ADMINISTRATOR->value]);
        $hasAll = $user->hasAllCachedRoles([UserRole::AUTHOR->value, UserRole::EDITOR->value]);

        // Assert
        expect($hasAny)->toBeTrue(); // Has author role
        expect($hasAll)->toBeTrue(); // Has both roles
    });

    it('handles users with no roles', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $cachedRoles = $user->getCachedRoles();
        $cachedPermissions = $user->getCachedPermissions();

        // Assert
        expect($cachedRoles)->toBe([]);
        expect($cachedPermissions)->toBe([]);
        expect($user->hasCachedRole('admin'))->toBeFalse();
        expect($user->hasCachedPermission('edit_posts'))->toBeFalse();
    });

    it('handles users with no permissions', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $cachedPermissions = $user->getCachedPermissions();

        // Assert - Subscriber role should have some permissions from seeder
        expect($cachedPermissions)->not->toBeEmpty();
        expect($user->hasCachedPermission('edit_posts'))->toBeFalse();
    });

    it('refreshes cache correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache initial data
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act
        $user->refreshCache();

        // Assert - Cache should be rebuilt
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
        expect($user->getCachedRoles())->toBe([UserRole::AUTHOR->value]);
    });

    it('handles cache versioning correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache with version 1
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act - Increment cache version
        Cache::put('user_cache_version', 2, CacheKeys::CACHE_TTL);

        // Assert - New cache should be created with version 2
        $user->getCachedRoles(); // This should create v2 cache
        expect(Cache::has('user_roles_'.$user->id.'_v2'))->toBeTrue();
    });

    it('handles permission changes through role updates', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $permission = Permission::where('name', 'publish_posts')->first();

        // Check if permission is already attached to avoid duplicates
        if (! $role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        // Cache initial permissions
        $user->getCachedPermissions();
        expect(Cache::has('user_permissions_'.$user->id.'_v1'))->toBeTrue();

        // Act - Update role permissions
        $role->update(['name' => 'Updated Author']);

        // Assert - New cache should be created with updated data
        $user->getCachedPermissions();
        expect(Cache::has('user_permissions_'.$user->id.'_v2'))->toBeTrue();
    });

    it('handles role deletion correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache initial data
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act - Delete role
        $role->delete();

        // Assert - Cache should be invalidated (new version created)
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v2'))->toBeTrue();
    });

    it('handles user deletion correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache initial data
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act - Delete user
        $user->delete();

        // Assert - Cache should be cleared
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeFalse();
    });

    it('handles multiple users with same role efficiently', function () {
        // Arrange
        $users = User::factory()->count(5)->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();

        foreach ($users as $user) {
            $user->roles()->attach($role->id);
        }

        // Cache data for all users
        foreach ($users as $user) {
            $user->getCachedRoles();
            expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
        }

        // Act - Update role (should invalidate all user caches efficiently)
        $role->update(['name' => 'Updated Author']);

        // Assert - New caches should be created with new version
        $users[0]->getCachedRoles();
        expect(Cache::has('user_roles_'.$users[0]->id.'_v2'))->toBeTrue();
    });

    it('handles complex permission scenarios', function () {
        // Arrange
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $editorRole = Role::where('name', UserRole::EDITOR->value)->first();

        $publishPermission = Permission::where('name', 'publish_posts')->first();
        $editPermission = Permission::where('name', 'edit_posts')->first();
        $deletePermission = Permission::where('name', 'delete_posts')->first();

        // Check if permissions are already attached to avoid duplicates
        if (! $authorRole->permissions()->where('permission_id', $publishPermission->id)->exists()) {
            $authorRole->permissions()->attach($publishPermission->id);
        }
        if (! $authorRole->permissions()->where('permission_id', $editPermission->id)->exists()) {
            $authorRole->permissions()->attach($editPermission->id);
        }
        if (! $editorRole->permissions()->where('permission_id', $editPermission->id)->exists()) {
            $editorRole->permissions()->attach($editPermission->id);
        }
        if (! $editorRole->permissions()->where('permission_id', $deletePermission->id)->exists()) {
            $editorRole->permissions()->attach($deletePermission->id);
        }

        $user->roles()->attach([$authorRole->id, $editorRole->id]);

        // Act
        $cachedPermissions = $user->getCachedPermissions();

        // Assert - Should have unique permissions from both roles
        expect($cachedPermissions)->toContain('publish_posts');
        expect($cachedPermissions)->toContain('edit_posts');
        expect($cachedPermissions)->toContain('delete_posts');
        expect(count($cachedPermissions))->toBeGreaterThanOrEqual(3); // At least 3 unique permissions
    });

    it('handles empty permission arrays correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $hasAny = $user->hasAnyCachedPermission([]);
        $hasAll = $user->hasAllCachedPermissions([]);

        // Assert
        expect($hasAny)->toBeFalse();
        expect($hasAll)->toBeTrue(); // Empty array means user has all required permissions
    });

    it('handles empty role arrays correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $hasAny = $user->hasAnyCachedRole([]);
        $hasAll = $user->hasAllCachedRoles([]);

        // Assert
        expect($hasAny)->toBeFalse();
        expect($hasAll)->toBeTrue(); // Empty array means user has all required roles
    });

    it('handles non-existent permissions correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $hasPermission = $user->hasCachedPermission('non_existent_permission');
        $hasAny = $user->hasAnyCachedPermission(['non_existent_permission', 'another_fake']);
        $hasAll = $user->hasAllCachedPermissions(['non_existent_permission']);

        // Assert
        expect($hasPermission)->toBeFalse();
        expect($hasAny)->toBeFalse();
        expect($hasAll)->toBeFalse();
    });

    it('handles non-existent roles correctly', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Act
        $hasRole = $user->hasCachedRole('non_existent_role');
        $hasAny = $user->hasAnyCachedRole(['non_existent_role', 'another_fake']);
        $hasAll = $user->hasAllCachedRoles(['non_existent_role']);

        // Assert
        expect($hasRole)->toBeFalse();
        expect($hasAny)->toBeFalse();
        expect($hasAll)->toBeFalse();
    });
});
