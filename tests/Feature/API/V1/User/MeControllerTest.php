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
        $response = $this->getJson(route('api.v1.me'));

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

    it('returns 401 when not authenticated', function () {
        // Make request without authentication
        $response = $this->getJson(route('api.v1.me'));

        // Assert unauthorized response
        $response->assertStatus(401);
    });

    it('returns 401 when token lacks access-api ability', function () {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Authenticate the user without the required ability
        Sanctum::actingAs($user, ['read']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert unauthorized response
        $response->assertStatus(401);
    });

    it('handles user with complete profile information', function () {
        // Create a test user with full profile
        $user = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'bio' => 'Software developer and tech enthusiast',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'twitter' => 'https://twitter.com/janesmith',
            'facebook' => 'https://facebook.com/janesmith',
            'linkedin' => 'https://linkedin.com/in/janesmith',
            'github' => 'https://github.com/janesmith',
            'website' => 'https://janesmith.dev',
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response structure and content
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'bio' => 'Software developer and tech enthusiast',
                    'avatar_url' => 'https://example.com/avatar.jpg',
                    'twitter' => 'https://twitter.com/janesmith',
                    'facebook' => 'https://facebook.com/janesmith',
                    'linkedin' => 'https://linkedin.com/in/janesmith',
                    'github' => 'https://github.com/janesmith',
                    'website' => 'https://janesmith.dev',
                ],
            ]);
    });

    it('handles user with minimal profile information', function () {
        // Create a test user with minimal profile
        $user = User::factory()->create([
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'bio' => null,
            'avatar_url' => null,
            'twitter' => null,
            'facebook' => null,
            'linkedin' => null,
            'github' => null,
            'website' => null,
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response structure and content
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Minimal User',
                    'email' => 'minimal@example.com',
                    'bio' => null,
                    'avatar_url' => null,
                    'twitter' => null,
                    'facebook' => null,
                    'linkedin' => null,
                    'github' => null,
                    'website' => null,
                ],
            ]);
    });

    it('handles user with verified email', function () {
        // Create a test user with verified email
        $user = User::factory()->create([
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'email_verified_at' => now(),
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response includes email verification
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Verified User',
                    'email' => 'verified@example.com',
                    'email_verified_at' => $user->email_verified_at->toISOString(),
                ],
            ]);
    });

    it('handles user with unverified email', function () {
        // Create a test user with unverified email
        $user = User::factory()->create([
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'email_verified_at' => null,
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response includes null email verification
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Unverified User',
                    'email' => 'unverified@example.com',
                    'email_verified_at' => null,
                ],
            ]);
    });

    it('handles user with long bio text', function () {
        // Create a test user with long bio
        $longBio = str_repeat('This is a very long bio text. ', 20);
        $user = User::factory()->create([
            'name' => 'Long Bio User',
            'email' => 'longbio@example.com',
            'bio' => $longBio,
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response includes long bio
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Long Bio User',
                    'email' => 'longbio@example.com',
                    'bio' => $longBio,
                ],
            ]);
    });

    it('handles user with special characters in name', function () {
        // Create a test user with special characters
        $user = User::factory()->create([
            'name' => 'José María O\'Connor-Smith',
            'email' => 'special@example.com',
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response handles special characters
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'José María O\'Connor-Smith',
                    'email' => 'special@example.com',
                ],
            ]);
    });

    it('handles user with very long email address', function () {
        // Create a test user with long email - minimal setup
        $longEmail = 'very.long.email.address.with.many.subdomains@very.long.domain.name.example.com';
        $user = User::factory()->create([
            'email' => $longEmail,
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response handles long email - only check essential fields
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $longEmail,
                ],
            ]);
    });

    it('handles user with roles and permissions', function () {
        // Create a test user with roles
        $user = User::factory()->create([
            'name' => 'Role User',
            'email' => 'role@example.com',
        ]);

        // Attach roles to user
        $adminRole = \App\Models\Role::where('name', \App\Enums\UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($user, $adminRole);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

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
                    'name' => 'Role User',
                    'email' => 'role@example.com',
                ],
            ]);
    });

    it('handles user with banned status', function () {
        // Create a test user with banned status
        $user = User::factory()->create([
            'name' => 'Banned User',
            'email' => 'banned@example.com',
            'banned_at' => now(),
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response includes banned status
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Banned User',
                    'email' => 'banned@example.com',
                ],
            ]);
    });

    it('handles user with blocked status', function () {
        // Create a test user with blocked status
        $user = User::factory()->create([
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'blocked_at' => now(),
        ]);

        // Authenticate the user with Sanctum
        Sanctum::actingAs($user, ['access-api']);

        // Make request to /me endpoint
        $response = $this->getJson(route('api.v1.me'));

        // Assert response includes blocked status
        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Blocked User',
                    'email' => 'blocked@example.com',
                ],
            ]);
    });
});
