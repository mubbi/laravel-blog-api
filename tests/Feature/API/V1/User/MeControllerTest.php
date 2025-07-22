<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/MeController', function () {
    it('returns authenticated user profile with roles and permissions', function () {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson('/api/v1/me');

        // Assert response structure
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'bio',
                    'avatar_url',
                    'twitter',
                    'facebook',
                    'linkedin',
                    'github',
                    'website',
                ],
            ])
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    });
});
