<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/ShowUserController', function () {
    it('can show a user with full details', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'banned_at' => null,
            'blocked_at' => null,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id', 'name', 'email', 'email_verified_at', 'banned_at', 'blocked_at',
                    'status', 'created_at', 'updated_at',
                    'roles' => [
                        '*' => [
                            'id', 'name', 'display_name',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'banned_at' => null,
                    'blocked_at' => null,
                ],
            ]);
    });

    it('can show a banned user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $bannedUser = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => null,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $bannedUser));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $bannedUser->id,
                    'banned_at' => $bannedUser->banned_at->toISOString(),
                    'blocked_at' => null,
                ],
            ]);
    });

    it('can show a blocked user', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $blockedUser = User::factory()->create([
            'banned_at' => null,
            'blocked_at' => now(),
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $blockedUser));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $blockedUser->id,
                    'banned_at' => null,
                    'blocked_at' => $blockedUser->blocked_at->toISOString(),
                ],
            ]);
    });

    it('can show a user with both banned and blocked status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create([
            'banned_at' => now()->subDays(5),
            'blocked_at' => now()->subDays(2),
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'banned_at' => $user->banned_at->toISOString(),
                    'blocked_at' => $user->blocked_at->toISOString(),
                ],
            ]);
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', 99999));

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        // Don't attach any roles to test authorization failure

        $token = $user->createToken('test-token', ['access-api']);

        $targetUser = User::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $targetUser));

        // Assert
        $response->assertStatus(403);
    });

    it('includes user roles in response', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'roles' => [
                        '*' => [
                            'id', 'name', 'display_name',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'data' => [
                    'roles' => [
                        [
                            'id' => $authorRole->id,
                            'name' => UserRole::AUTHOR->value,
                            'display_name' => UserRole::AUTHOR->displayName(),
                        ],
                    ],
                ],
            ]);
    });

    it('handles users with multiple roles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $editorRole = Role::where('name', UserRole::EDITOR->value)->first();
        $user->roles()->attach([$authorRole->id, $editorRole->id]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'roles' => [
                        '*' => [
                            'id', 'name', 'display_name',
                        ],
                    ],
                ],
            ]);

        $responseData = $response->json('data.roles');
        $this->assertCount(2, $responseData);

        $roleNames = collect($responseData)->pluck('name')->toArray();
        $this->assertContains(UserRole::AUTHOR->value, $roleNames);
        $this->assertContains(UserRole::EDITOR->value, $roleNames);
    });

    it('handles users with no roles', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $user = User::factory()->create();

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $user));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'email_verified_at', 'banned_at', 'blocked_at',
                    'status', 'created_at', 'updated_at', 'roles',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'roles' => [],
                ],
            ]);
    });

    it('includes email verification status', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $verifiedUser));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $verifiedUser->id,
                    'email_verified_at' => $verifiedUser->email_verified_at->toISOString(),
                ],
            ]);
    });

    it('handles unverified email users', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->getJson(route('api.v1.admin.users.show', $unverifiedUser));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $unverifiedUser->id,
                    'email_verified_at' => null,
                ],
            ]);
    });
});
