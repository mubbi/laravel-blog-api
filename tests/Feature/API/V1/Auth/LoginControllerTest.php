<?php

declare(strict_types=1);

use App\Events\Auth\UserLoggedInEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Mockery\MockInterface;

describe('API/V1/Auth/LoginController', function () {
    it('can login with valid credentials', function () {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('J{kj)6ig8x51'),
        ]);

        // Attempt login
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'J{kj)6ig8x51',
        ]);

        // Check response
        $response->assertStatus(200);
    });

    it('returns 422 validation error when password is missing', function () {
        // Attempt login with only email
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
        ]);

        // Check response
        $response->assertStatus(422);
    });

    it('returns 401 when AuthService throws UnauthorizedException', function () {
        // Mock the AuthServiceInterface - no user creation needed since service is mocked
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('login')
                ->with('test@example.com', 'ValidPass123!')
                ->andThrow(new UnauthorizedException('Invalid credentials'));
        });

        // Attempt login with invalid credentials
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'ValidPass123!',
        ]);

        // Check response
        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => __('auth.failed'),
                'data' => null,
                'error' => null,
            ]);
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        // Mock the AuthServiceInterface - no user creation needed since service is mocked
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('login')
                ->with('test@example.com', 'AnotherValid123!')
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        // Attempt login which will trigger unexpected exception
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'AnotherValid123!',
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

    it('can login and returns user with roles and permissions', function () {
        // Create permissions with unique slugs
        $permission1 = Permission::factory()->create(['slug' => 'test-read-'.uniqid()]);
        $permission2 = Permission::factory()->create(['slug' => 'test-write-'.uniqid()]);

        // Create role with unique slug and attach permissions
        $role = Role::factory()->create(['slug' => 'test-editor-'.uniqid()]);
        $role->permissions()->attach([$permission1->id, $permission2->id]);

        // Create user with role
        $user = User::factory()->create([
            'email' => 'testeditor@example.com',
            'password' => Hash::make('EditorPass123!'),
        ]);
        $user->roles()->attach($role->id);

        // Attempt login
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'testeditor@example.com',
            'password' => 'EditorPass123!',
        ]);

        // Check response status and basic structure
        $response->assertStatus(200);

        // Get the response data to verify roles and permissions are included
        $responseData = $response->json('data');

        // Verify the roles and permissions are included in response
        expect($responseData['roles'])->toContain($role->slug);
        expect($responseData['permissions'])->toContain($permission1->slug);
        expect($responseData['permissions'])->toContain($permission2->slug);
    });

    it('dispatches UserLoggedInEvent when user logs in successfully', function () {
        // Arrange
        Event::fake([UserLoggedInEvent::class]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestP@ss123'),
        ]);

        // Act
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'TestP@ss123',
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserLoggedInEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->user->email === 'test@example.com';
        });
    });
});
