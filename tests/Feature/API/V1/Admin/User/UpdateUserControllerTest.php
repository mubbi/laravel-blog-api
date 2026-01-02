<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserUpdatedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/UpdateUserController', function () {
    it('can update a user successfully', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

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
                    'id' => $userToUpdate->id,
                    'name' => 'New Name',
                    'email' => 'new@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    it('can update only name', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'test@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'test@example.com', // Should remain unchanged
        ]);
    });

    it('can update only email', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create([
            'name' => 'Test User',
            'email' => 'old@example.com',
        ]);

        $updateData = [
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Test User', // Should remain unchanged
            'email' => 'new@example.com',
        ]);
    });

    it('returns 404 when user does not exist', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', 99999), $updateData);

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => __('common.user_not_found'),
            ]);
    });

    it('returns 401 when user is not authenticated', function () {
        // Arrange
        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $token = $user->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(403);
    });

    it('validates email format', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => 'New Name',
            'email' => 'invalid-email',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email field must be a valid email address.'],
                ],
            ]);
    });

    it('validates email uniqueness', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $userToUpdate = User::factory()->create(['email' => 'test@example.com']);

        $updateData = [
            'name' => 'New Name',
            'email' => 'existing@example.com', // Already exists
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
    });

    it('allows updating to same email', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'test@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'test@example.com', // Same email
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'test@example.com',
        ]);
    });

    it('validates name is required when provided', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => '', // Empty name
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'name' => ['The name field is required.'],
                ],
            ]);
    });

    it('validates name length', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create();

        $updateData = [
            'name' => str_repeat('a', 256), // Too long
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                ],
            ]);
    });

    it('maintains other user properties when updating', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $originalData = [
            'email_verified_at' => now(),
            'banned_at' => now()->subDays(5),
            'blocked_at' => now()->subDays(2),
        ];

        $userToUpdate = User::factory()->create($originalData);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(200);

        $userToUpdate->refresh();
        $this->assertEquals('New Name', $userToUpdate->name);
        $this->assertEquals('new@example.com', $userToUpdate->email);
        $this->assertEquals($originalData['email_verified_at']->toDateTimeString(), $userToUpdate->email_verified_at->toDateTimeString());
        $this->assertEquals($originalData['banned_at']->toDateTimeString(), $userToUpdate->banned_at->toDateTimeString());
        $this->assertEquals($originalData['blocked_at']->toDateTimeString(), $userToUpdate->blocked_at->toDateTimeString());
    });

    it('allows admin to update themselves', function () {
        // Arrange
        $admin = User::factory()->create([
            'name' => 'Old Admin Name',
            'email' => 'admin@example.com',
        ]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $updateData = [
            'name' => 'New Admin Name',
            'email' => 'newadmin@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $admin), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'New Admin Name',
            'email' => 'newadmin@example.com',
        ]);
    });

    it('dispatches UserUpdatedEvent when user is updated', function () {
        // Arrange
        Event::fake([UserUpdatedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $token = $admin->createToken('test-token', ['access-api']);

        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), $updateData);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserUpdatedEvent::class, function ($event) use ($userToUpdate) {
            return $event->user->id === $userToUpdate->id
                && $event->user->name === 'New Name'
                && $event->user->email === 'new@example.com';
        });
    });
});
