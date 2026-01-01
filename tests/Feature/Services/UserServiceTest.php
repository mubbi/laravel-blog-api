<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Data\FilterUserDTO;
use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;

describe('UserService Caching', function () {
    beforeEach(function () {
        // Clear all caches before each test
        Cache::flush();
    });

    it('caches roles and permissions when getting user by ID', function () {
        // Arrange
        $userService = app(UserService::class);
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();
        $permission = Permission::where('name', 'publish_posts')->first();

        // Check if permission is already attached to avoid duplicates
        if (! $role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach($permission->id);
        }
        $user->roles()->attach($role->id);

        // Act
        $retrievedUser = $userService->getUserById($user->id);

        // Assert
        expect($retrievedUser->id)->toBe($user->id);
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
        expect(Cache::has('user_permissions_'.$user->id.'_v1'))->toBeTrue();
    });

    it('caches all roles with permissions', function () {
        // Arrange
        $userService = app(UserService::class);

        // Act
        $roles = $userService->getAllRoles();

        // Assert
        expect($roles)->not->toBeEmpty();
        expect(Cache::has(CacheKeys::ALL_ROLES_CACHE_KEY))->toBeTrue();
    });

    it('caches all permissions', function () {
        // Arrange
        $userService = app(UserService::class);

        // Act
        $permissions = $userService->getAllPermissions();

        // Assert
        expect($permissions)->not->toBeEmpty();
        expect(Cache::has(CacheKeys::ALL_PERMISSIONS_CACHE_KEY))->toBeTrue();
    });

    it('clears user cache when assigning roles', function () {
        // Arrange
        $userService = app(UserService::class);
        $user = User::factory()->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();

        // Cache some data first
        $user->getCachedRoles();
        expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();

        // Act
        $updatedUser = $userService->assignRoles($user->id, [$role->id]);

        // Assert
        expect($updatedUser->id)->toBe($user->id);
        expect($updatedUser->roles)->toHaveCount(1);
    });

    it('pre-warms caches for users in pagination', function () {
        // Arrange
        $userService = app(UserService::class);
        $users = User::factory()->count(3)->create();
        $role = Role::where('name', UserRole::AUTHOR->value)->first();

        foreach ($users as $user) {
            $user->roles()->attach($role->id);
        }

        // Act
        $dto = new FilterUserDTO(
            page: 1,
            perPage: 2,
            sortBy: 'created_at',
            sortDirection: 'desc'
        );
        $paginator = $userService->getUsersWithWarmedCaches($dto);

        // Assert
        expect($paginator->total())->toBeGreaterThanOrEqual(3); // At least 3 users (including seeded ones)
        expect($paginator->items())->toHaveCount(2); // First page

        // Check that caches are warmed for first page users
        foreach ($paginator->items() as $user) {
            expect(Cache::has('user_roles_'.$user->id.'_v1'))->toBeTrue();
            expect(Cache::has('user_permissions_'.$user->id.'_v1'))->toBeTrue();
        }
    });

    it('increments cache version correctly', function () {
        // Arrange
        $userService = app(UserService::class);
        $initialVersion = Cache::get('user_cache_version', 1);
        expect($initialVersion)->toBe(1);

        // Act
        $userService->incrementCacheVersion();

        // Assert
        $newVersion = Cache::get('user_cache_version');
        expect($newVersion)->toBe(2);
    });
});
