<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\Auth\UserLoggedInEvent;
use App\Events\User\UserCreatedEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

describe('API/V1/Auth/RegisterController', function () {
    it('can register a new user with valid data', function () {
        // Ensure subscriber role exists
        $subscriberRole = Role::firstOrCreate(
            ['name' => UserRole::SUBSCRIBER->value],
            ['slug' => 'subscriber']
        );

        // Attempt registration
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        // Check response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'permissions',
                    'access_token',
                    'refresh_token',
                    'token_type',
                ],
            ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        // Verify user has subscriber role
        $user = User::where('email', 'john@example.com')->first();
        expect($user)->not->toBeNull();
        expect($user->roles->pluck('slug')->toArray())->toContain('subscriber');
    });

    it('can register with optional profile fields', function () {
        // Ensure subscriber role exists
        $subscriberRole = Role::firstOrCreate(
            ['name' => UserRole::SUBSCRIBER->value],
            ['slug' => 'subscriber']
        );

        // Attempt registration with optional fields
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'TestRegP@ss2024!',
            'bio' => 'Software developer',
            'twitter' => '@jane',
            'github' => 'jane-doe',
            'website' => 'https://jane.example.com',
        ]);

        // Check response
        $response->assertStatus(201);

        // Verify user was created with optional fields
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'bio' => 'Software developer',
            'twitter' => '@jane',
            'github' => 'jane-doe',
            'website' => 'https://jane.example.com',
        ]);
    });

    it('returns 422 validation error when email is missing', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'password' => 'TestRegP@ss2024!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 validation error when name is missing', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('returns 422 validation error when password is missing', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns 422 validation error when email already exists', function () {
        // Create existing user
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Attempt registration with same email
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 validation error when password does not meet requirements', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak', // Too weak
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('returns 422 validation error when email format is invalid', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'TestRegP@ss2024!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 validation error when website URL is invalid', function () {
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
            'website' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        // Mock the AuthServiceInterface
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('register')
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        // Attempt registration which will trigger unexpected exception
        // Note: We use a valid password format here since we're testing exception handling,
        // but the mock will throw before validation
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        // Check response
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('dispatches UserCreatedEvent and UserLoggedInEvent when user registers successfully', function () {
        // Arrange
        Event::fake([UserCreatedEvent::class, UserLoggedInEvent::class]);

        // Ensure subscriber role exists
        $subscriberRole = Role::firstOrCreate(
            ['name' => UserRole::SUBSCRIBER->value],
            ['slug' => 'subscriber']
        );

        // Act
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        // Assert
        $response->assertStatus(201);

        Event::assertDispatched(UserCreatedEvent::class, function ($event) {
            return $event->user->email === 'john@example.com'
                && $event->user->name === 'John Doe';
        });

        Event::assertDispatched(UserLoggedInEvent::class, function ($event) {
            return $event->user->email === 'john@example.com';
        });
    });

    it('returns user with access and refresh tokens after registration', function () {
        // Ensure subscriber role exists
        $subscriberRole = Role::firstOrCreate(
            ['name' => UserRole::SUBSCRIBER->value],
            ['slug' => 'subscriber']
        );

        // Attempt registration
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        // Check response
        $response->assertStatus(201);

        $responseData = $response->json('data');

        // Verify tokens are present
        expect($responseData['access_token'])->toBeString();
        expect($responseData['refresh_token'])->toBeString();
        expect($responseData['token_type'])->toBe('Bearer');
        expect($responseData['access_token_expires_at'])->not->toBeNull();
        expect($responseData['refresh_token_expires_at'])->not->toBeNull();
    });

    it('returns user with roles and permissions after registration', function () {
        // Ensure subscriber role exists
        $subscriberRole = Role::firstOrCreate(
            ['name' => UserRole::SUBSCRIBER->value],
            ['slug' => 'subscriber']
        );

        // Attempt registration
        $response = $this->postJson(route('api.v1.auth.register'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestRegP@ss2024!',
        ]);

        // Check response
        $response->assertStatus(201);

        $responseData = $response->json('data');

        // Verify roles and permissions are included
        expect($responseData['roles'])->toBeArray();
        expect($responseData['permissions'])->toBeArray();
        expect($responseData['roles'])->toContain('subscriber');
    });
});
