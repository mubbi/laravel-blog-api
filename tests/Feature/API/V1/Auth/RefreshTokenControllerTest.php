<?php

declare(strict_types=1);

use App\Events\Auth\TokenRefreshedEvent;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Event;
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
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        // Attempt to refresh token which will trigger unexpected exception
        $response = $this->postJson(route('api.v1.auth.refresh'), [
            'refresh_token' => 'some-refresh-token',
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

    it('dispatches TokenRefreshedEvent when token is refreshed successfully', function () {
        // Arrange
        Event::fake([TokenRefreshedEvent::class]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Create a real refresh token (don't mock the service to allow event dispatch)
        $refreshTokenExpiration = now()->addDays(30);
        $refreshToken = $user->createToken(
            'refresh_token',
            ['refresh-token'],
            $refreshTokenExpiration
        );

        // Act
        $response = $this->postJson(route('api.v1.auth.refresh'), [
            'refresh_token' => $refreshToken->plainTextToken,
        ]);

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(TokenRefreshedEvent::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    });
});
