<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

describe('ClearRolePermissionCache Command', function () {
    beforeEach(function () {
        // Clear all caches before each test
        Cache::flush();
    });

    it('clears cache for specific user', function () {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $user->roles()->attach($role->id);

        // Cache some data
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act
        $this->artisan('cache:clear-roles-permissions', ['--user-id' => $user->id])
            ->expectsOutput(__('console.cache_cleared_for_user', ['name' => $user->name, 'id' => $user->id]))
            ->assertExitCode(0);

        // Assert
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeFalse();
    });

    it('handles non-existent user gracefully', function () {
        // Act
        $this->artisan('cache:clear-roles-permissions', ['--user-id' => 99999])
            ->expectsOutput(__('console.user_not_found', ['id' => 99999]))
            ->assertExitCode(1);
    });

    it('clears all user caches by incrementing version', function () {
        // Arrange
        $users = User::factory()->count(3)->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();

        foreach ($users as $user) {
            $user->roles()->attach($role->id);
            $user->getCachedRoles();
            expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
        }

        // Act
        $this->artisan('cache:clear-roles-permissions', ['--all' => true])
            ->expectsOutput(__('console.all_user_caches_cleared'))
            ->assertExitCode(0);

        // Assert - New cache should be created with version 2
        $users[0]->getCachedRoles();
        expect(Cache::has('user_roles_'.$users[0]->id.'_v2'))->toBeTrue();
    });

    it('clears global caches when no options provided', function () {
        // Arrange - Set up some global caches
        Cache::put(CacheKeys::ALL_ROLES_CACHE_KEY, ['test'], CacheKeys::CACHE_TTL);
        Cache::put(CacheKeys::ALL_PERMISSIONS_CACHE_KEY, ['test'], CacheKeys::CACHE_TTL);

        expect(Cache::has(CacheKeys::ALL_ROLES_CACHE_KEY))->toBeTrue();
        expect(Cache::has(CacheKeys::ALL_PERMISSIONS_CACHE_KEY))->toBeTrue();

        // Act
        $this->artisan('cache:clear-roles-permissions')
            ->expectsOutput(__('console.global_caches_cleared'))
            ->assertExitCode(0);

        // Assert
        expect(Cache::has(CacheKeys::ALL_ROLES_CACHE_KEY))->toBeFalse();
        expect(Cache::has(CacheKeys::ALL_PERMISSIONS_CACHE_KEY))->toBeFalse();
    });

    it('increments cache version correctly', function () {
        // Arrange
        $initialVersion = Cache::get('user_cache_version', 1);
        expect($initialVersion)->toBe(1);

        // Act
        $this->artisan('cache:clear-roles-permissions', ['--all' => true])
            ->expectsOutput(__('console.cache_version_incremented', ['from' => 1, 'to' => 2]))
            ->expectsOutput(__('console.all_user_caches_invalidated'))
            ->assertExitCode(0);

        // Assert
        $newVersion = Cache::get('user_cache_version');
        expect($newVersion)->toBe(2);
    });

    it('handles multiple version increments', function () {
        // Arrange
        Cache::put('user_cache_version', 5, CacheKeys::CACHE_TTL);

        // Act
        $this->artisan('cache:clear-roles-permissions', ['--all' => true])
            ->expectsOutput(__('console.cache_version_incremented', ['from' => 5, 'to' => 6]))
            ->expectsOutput(__('console.all_user_caches_invalidated'))
            ->assertExitCode(0);

        // Assert
        $newVersion = Cache::get('user_cache_version');
        expect($newVersion)->toBe(6);
    });
});
