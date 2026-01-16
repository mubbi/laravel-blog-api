<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;

describe('Role Model', function () {
    beforeEach(function () {
        // Restore event dispatcher for Eloquent models to allow model events to run
        // This test suite depends on model event callbacks (Role::boot())
        // to properly increment cache versions when roles are updated/deleted
        // Event::fake() in TestCase prevents all events, including model events
        // So we need to set a real dispatcher for models specifically
        Model::setEventDispatcher(new Dispatcher);

        // Clear cache before each test to ensure clean state
        Cache::forget('user_cache_version');
    });
    it('can be created', function () {
        // Act
        $role = Role::factory()->create();

        // Assert
        expect($role->name)->not->toBeEmpty();
        expect($role->slug)->not->toBeEmpty();
        expect($role->id)->toBeInt();
    });

    it('has permissions relationship', function () {
        // Arrange
        $role = Role::factory()->create();
        $permission1 = Permission::factory()->create();
        $permission2 = Permission::factory()->create();

        $role->permissions()->attach([$permission1->id, $permission2->id]);

        // Act
        $permissions = $role->permissions;

        // Assert
        expect($permissions)->toHaveCount(2);
        expect($permissions->pluck('id')->toArray())->toContain($permission1->id, $permission2->id);
    });

    it('has users relationship', function () {
        // Arrange
        $role = Role::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role->users()->attach([$user1->id, $user2->id]);

        // Act
        $users = $role->users;

        // Assert
        expect($users)->toHaveCount(2);
        expect($users->pluck('id')->toArray())->toContain($user1->id, $user2->id);
    });

    it('clears user caches when role is updated', function () {
        // Arrange
        Cache::forget('user_cache_version');
        $initialVersion = 1;
        Cache::put('user_cache_version', $initialVersion, CacheKeys::CACHE_TTL);
        $role = Role::factory()->create();

        // Verify initial state
        expect(Cache::get('user_cache_version'))->toBe($initialVersion);

        // Act - Force update to ensure event fires
        $role->name = 'Updated Role';
        $role->save();

        // Assert
        $newVersion = Cache::get('user_cache_version');
        expect($newVersion)->not->toBeNull();
        expect($newVersion)->toBe($initialVersion + 1);
    });

    it('clears user caches when role is deleted', function () {
        // Arrange
        Cache::forget('user_cache_version');
        $initialVersion = 1;
        Cache::put('user_cache_version', $initialVersion, CacheKeys::CACHE_TTL);
        $role = Role::factory()->create();

        // Verify initial state
        expect(Cache::get('user_cache_version'))->toBe($initialVersion);

        // Act - Delete role (the deleted event should fire and increment cache)
        $roleId = $role->id;
        $role->delete();

        // Ensure the role is actually deleted
        expect(Role::find($roleId))->toBeNull();

        // Assert - Cache version should be incremented by the deleted event
        $newVersion = Cache::get('user_cache_version');
        expect($newVersion)->not->toBeNull();
        expect($newVersion)->toBe($initialVersion + 1);
    });

    it('can attach permissions to role', function () {
        // Arrange
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();

        // Act
        $role->permissions()->attach($permission->id);

        // Assert
        expect($role->permissions)->toHaveCount(1);
        expect($role->permissions->first()->id)->toBe($permission->id);
    });

    it('can detach permissions from role', function () {
        // Arrange
        $role = Role::factory()->create();
        $permission = Permission::factory()->create();
        $role->permissions()->attach($permission->id);

        // Act
        $role->permissions()->detach($permission->id);

        // Assert
        expect($role->fresh()->permissions)->toHaveCount(0);
    });

    it('has timestamps', function () {
        // Arrange
        $role = Role::factory()->create();

        // Assert
        expect($role->created_at)->not->toBeNull();
        expect($role->updated_at)->not->toBeNull();
    });
});
