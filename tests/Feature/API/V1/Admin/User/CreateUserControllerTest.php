<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/CreateUserController', function () {
    it('can create a new user with valid data', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'bio' => 'A test user bio',
            'twitter' => 'johndoe',
            'role_id' => $authorRole->id,
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'bio',
                    'twitter',
                    'roles',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        expect($user->roles)->toHaveCount(1);
        expect($user->roles->first()->name)->toBe(UserRole::AUTHOR->value);
    });

    it('can create a user without optional fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    });

    it('validates required fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), []);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The name field is required. (and 2 more errors)',
                'data' => null,
                'error' => [
                    'name' => ['The name field is required.'],
                    'email' => ['The email field is required.'],
                    'password' => ['The password field is required.'],
                ],
            ]);
    });

    it('validates email uniqueness', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $existingUser = User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The email has already been taken.',
                'data' => null,
                'error' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
    });

    it('validates password minimum length', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The password field must be at least 8 characters.',
                'data' => null,
                'error' => [
                    'password' => ['The password field must be at least 8 characters.'],
                ],
            ]);
    });

    it('validates URL fields', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'avatar_url' => 'not-a-url',
            'website' => 'invalid-url',
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The avatar url field must be a valid URL. (and 1 more error)',
                'data' => null,
                'error' => [
                    'avatar_url' => ['The avatar url field must be a valid URL.'],
                    'website' => ['The website field must be a valid URL.'],
                ],
            ]);
    });

    it('validates role_id exists', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        $admin->roles()->attach($adminRole->id);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role_id' => 99999, // Non-existent role
        ];

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The selected role id is invalid.',
                'data' => null,
                'error' => [
                    'role_id' => ['The selected role id is invalid.'],
                ],
            ]);
    });

    it('returns 403 when user lacks create_users permission', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        $user->roles()->attach($subscriberRole->id);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        // Act
        $response = $this->postJson(route('api.v1.admin.users.store'), $userData);

        // Assert
        $response->assertStatus(401);
    });
});
