<?php

declare(strict_types=1);

use App\Constants\CacheKeys;
use App\Data\CreateUserDTO;
use App\Data\FilterUserDTO;
use App\Data\UpdateUserDTO;
use App\Events\User\UserBannedEvent;
use App\Events\User\UserBlockedEvent;
use App\Events\User\UserCreatedEvent;
use App\Events\User\UserDeletedEvent;
use App\Events\User\UserUnbannedEvent;
use App\Events\User\UserUnblockedEvent;
use App\Events\User\UserUpdatedEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('UserService', function () {
    beforeEach(function () {
        $this->service = app(UserService::class);
    });

    describe('getUsers', function () {
        it('can get paginated users', function () {
            // Arrange
            $existingCount = User::count();
            User::factory()->count(20)->create();
            $expectedTotal = $existingCount + 20;

            $dto = new FilterUserDTO(
                page: 1,
                perPage: 10
            );

            // Act
            $result = $this->service->getUsers($dto);

            // Assert
            expect($result->count())->toBe(min(10, $expectedTotal));
            expect($result->total())->toBe($expectedTotal);
        });

        it('can filter users by search', function () {
            // Arrange
            User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
            User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

            $dto = new FilterUserDTO(
                search: 'John'
            );

            // Act
            $result = $this->service->getUsers($dto);

            // Assert
            expect($result->total())->toBe(1);
            expect($result->items()[0]->name)->toContain('John');
        });

        it('can filter users by role', function () {
            // Arrange
            $role = Role::factory()->create();
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            $user1->roles()->attach($role->id);

            $dto = new FilterUserDTO(
                roleId: $role->id
            );

            // Act
            $result = $this->service->getUsers($dto);

            // Assert
            expect($result->total())->toBe(1);
            expect($result->items()[0]->id)->toBe($user1->id);
        });

        it('can filter users by status', function () {
            // Arrange
            User::factory()->create(['banned_at' => now()]);
            User::factory()->create(['banned_at' => null, 'blocked_at' => null]);

            $dto = new FilterUserDTO(
                status: 'banned'
            );

            // Act
            $result = $this->service->getUsers($dto);

            // Assert
            expect($result->total())->toBe(1);
            expect($result->items()[0]->banned_at)->not->toBeNull();
        });
    });

    describe('getUserById', function () {
        it('can get user by id with relationships', function () {
            // Arrange
            $user = User::factory()->create();
            $role = Role::factory()->create();
            $user->roles()->attach($role->id);

            // Act
            $result = $this->service->getUserById($user->id);

            // Assert
            expect($result->id)->toBe($user->id);
            expect($result->relationLoaded('roles'))->toBeTrue();
        });
    });

    describe('createUser', function () {
        it('creates a user successfully', function () {
            // Arrange
            Event::fake();
            $dto = new CreateUserDTO(
                name: 'John Doe',
                email: 'john@example.com',
                password: 'password123'
            );

            // Act
            $result = $this->service->createUser($dto);

            // Assert
            expect($result->name)->toBe('John Doe');
            expect($result->email)->toBe('john@example.com');
            expect($result->password)->not->toBe('password123'); // Should be hashed
            Event::assertDispatched(UserCreatedEvent::class);
        });

        it('assigns default role when provided', function () {
            // Arrange
            $role = Role::factory()->create();
            $dto = new CreateUserDTO(
                name: 'John Doe',
                email: 'john@example.com',
                password: 'password123',
                roleId: $role->id
            );

            // Act
            $result = $this->service->createUser($dto);

            // Assert
            expect($result->roles->pluck('id')->toArray())->toContain($role->id);
        });
    });

    describe('updateUser', function () {
        it('updates a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create(['name' => 'Old Name']);
            $dto = UpdateUserDTO::fromArray(['name' => 'New Name']);

            // Act
            $result = $this->service->updateUser($user, $dto);

            // Assert
            expect($result->name)->toBe('New Name');
            Event::assertDispatched(UserUpdatedEvent::class);
        });

        it('updates password when provided', function () {
            // Arrange
            $user = User::factory()->create();
            $oldPassword = $user->password;
            $dto = UpdateUserDTO::fromArray(['password' => 'newpassword123']);

            // Act
            $result = $this->service->updateUser($user, $dto);

            // Assert
            expect($result->password)->not->toBe($oldPassword);
        });
    });

    describe('deleteUser', function () {
        it('deletes a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create();
            $admin = User::factory()->create();

            // Act
            $result = $this->service->deleteUser($user, $admin);

            // Assert
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
            Event::assertDispatched(UserDeletedEvent::class);
        });

        it('prevents self-deletion', function () {
            // Arrange
            $user = User::factory()->create();

            // Act & Assert
            expect(fn () => $this->service->deleteUser($user, $user))
                ->toThrow(AuthorizationException::class);
        });
    });

    describe('banUser', function () {
        it('bans a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create(['banned_at' => null]);
            $admin = User::factory()->create();

            // Act
            $result = $this->service->banUser($user, $admin);

            // Assert
            expect($result->banned_at)->not->toBeNull();
            Event::assertDispatched(UserBannedEvent::class);
        });

        it('prevents self-ban', function () {
            // Arrange
            $user = User::factory()->create();

            // Act & Assert
            expect(fn () => $this->service->banUser($user, $user))
                ->toThrow(AuthorizationException::class);
        });
    });

    describe('unbanUser', function () {
        it('unbans a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create(['banned_at' => now()]);
            $admin = User::factory()->create();

            // Act
            $result = $this->service->unbanUser($user, $admin);

            // Assert
            expect($result->banned_at)->toBeNull();
            Event::assertDispatched(UserUnbannedEvent::class);
        });
    });

    describe('blockUser', function () {
        it('blocks a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create(['blocked_at' => null]);
            $admin = User::factory()->create();

            // Act
            $result = $this->service->blockUser($user, $admin);

            // Assert
            expect($result->blocked_at)->not->toBeNull();
            Event::assertDispatched(UserBlockedEvent::class);
        });
    });

    describe('unblockUser', function () {
        it('unblocks a user successfully', function () {
            // Arrange
            Event::fake();
            $user = User::factory()->create(['blocked_at' => now()]);
            $admin = User::factory()->create();

            // Act
            $result = $this->service->unblockUser($user, $admin);

            // Assert
            expect($result->blocked_at)->toBeNull();
            Event::assertDispatched(UserUnblockedEvent::class);
        });
    });

    describe('getAllRoles', function () {
        it('returns cached roles', function () {
            // Arrange
            Cache::forget(CacheKeys::ALL_ROLES_CACHE_KEY);
            $existingCount = Role::count();
            Role::factory()->count(3)->create();

            // Act
            $result = $this->service->getAllRoles();

            // Assert
            expect($result)->toHaveCount($existingCount + 3);
            expect(Cache::has(CacheKeys::ALL_ROLES_CACHE_KEY))->toBeTrue();
        });
    });

    describe('getAllPermissions', function () {
        it('returns cached permissions', function () {
            // Arrange
            Cache::forget(CacheKeys::ALL_PERMISSIONS_CACHE_KEY);

            // Act
            $result = $this->service->getAllPermissions();

            // Assert
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect(Cache::has(CacheKeys::ALL_PERMISSIONS_CACHE_KEY))->toBeTrue();
        });
    });

    describe('assignRoles', function () {
        it('assigns roles to user', function () {
            // Arrange
            $user = User::factory()->create();
            $role1 = Role::factory()->create();
            $role2 = Role::factory()->create();

            // Act
            $result = $this->service->assignRoles($user->id, [$role1->id, $role2->id]);

            // Assert
            expect($result->roles->pluck('id')->toArray())->toContain($role1->id, $role2->id);
        });
    });
});
