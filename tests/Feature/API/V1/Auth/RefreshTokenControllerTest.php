<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Validation\UnauthorizedException;
use Mockery\MockInterface;

describe('API/V1/Auth/RefreshTokenController', function () {
    it('can refresh token successfully with valid refresh token', function () {
        // Create a test user with token data
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Add token properties to the user object for the response
        $user->access_token = 'new-access-token';
        $user->refresh_token = 'new-refresh-token';
        $user->access_token_expires_at = now()->addMinutes(60);
        $user->refresh_token_expires_at = now()->addDays(30);

        // Mock the AuthServiceInterface
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('refreshToken')
                ->with('valid-refresh-token')
                ->once()
                ->andReturn($user);
        });

        // Attempt to refresh token
        $response = $this->postJson(route('api.v1.auth.refresh'), [
            'refresh_token' => 'valid-refresh-token',
        ]);

        // Check response
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('auth.token_refreshed_successfully'),
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'access_token' => 'new-access-token',
                    'refresh_token' => 'new-refresh-token',
                    'token_type' => 'Bearer',
                ],
            ]);
    });

    it('returns 401 when refresh token is invalid or expired', function () {
        // Mock the AuthServiceInterface to throw UnauthorizedException
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('refreshToken')
                ->with('invalid-refresh-token')
                ->once()
                ->andThrow(new UnauthorizedException('Invalid or expired refresh token'));
        });

        // Attempt to refresh token with invalid token
        $response = $this->postJson(route('api.v1.auth.refresh'), [
            'refresh_token' => 'invalid-refresh-token',
        ]);

        // Check response
        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid or expired refresh token',
                'data' => null,
                'error' => null,
            ]);
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        // Mock the AuthServiceInterface to throw an unexpected exception
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('refreshToken')
                ->with('some-refresh-token')
                ->once()
                ->andThrow(new \Exception('Database connection failed'));
        });

        // Attempt to refresh token which will trigger unexpected exception
        $response = $this->postJson(route('api.v1.auth.refresh'), [
            'refresh_token' => 'some-refresh-token',
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
