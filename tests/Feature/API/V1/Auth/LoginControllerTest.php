<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
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
        // Mock the AuthServiceInterface
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
        // Mock the AuthServiceInterface
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('login')
                ->with('test@example.com', 'AnotherValid123!')
                ->andThrow(new \Exception('Database connection failed'));
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
                'message' => __('An unexpected error occurred.'),
                'data' => null,
                'error' => null,
            ]);
    });
});
