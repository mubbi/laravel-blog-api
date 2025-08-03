<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

describe('API/V1/Auth/LogoutController', function () {
    it('can logout successfully with valid authenticated user', function () {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Authenticate the user using Sanctum with required abilities
        Sanctum::actingAs($user, ['access-api']);

        // Mock the AuthServiceInterface
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('logout')
                ->with($user)
                ->once()
                ->andReturnNull();
        });

        // Attempt logout
        $response = $this->postJson(route('api.v1.auth.logout'));

        // Check response
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('auth.logout_success'),
                'data' => null,
            ]);
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Authenticate the user using Sanctum with required abilities
        Sanctum::actingAs($user, ['access-api']);

        // Mock the AuthServiceInterface to throw an exception
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('logout')
                ->with($user)
                ->once()
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        // Attempt logout which will trigger unexpected exception
        $response = $this->postJson(route('api.v1.auth.logout'));

        // Check response
        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
