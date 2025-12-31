<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/User/UpdateProfileController', function () {
    it('can update user profile with valid data', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $updateData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'twitter' => 'updated_twitter',
            'website' => 'https://example.com',
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'bio',
                    'twitter',
                    'website',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'twitter' => 'updated_twitter',
            'website' => 'https://example.com',
        ]);
    });

    it('can update partial profile data', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $originalName = $user->name;
        $updateData = [
            'bio' => 'Only updating bio',
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $originalName, // Should remain unchanged
            'bio' => 'Only updating bio',
        ]);
    });

    it('validates URL fields', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $updateData = [
            'avatar_url' => 'not-a-url',
            'website' => 'invalid-url',
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

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

    it('validates string length limits', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $updateData = [
            'name' => str_repeat('a', 300), // Too long
            'bio' => str_repeat('b', 1500), // Too long
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The name field must not be greater than 255 characters. (and 1 more error)',
                'data' => null,
                'error' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                    'bio' => ['The bio field must not be greater than 1000 characters.'],
                ],
            ]);
    });

    it('returns 403 when user lacks edit_profile permission', function () {
        // Arrange
        // Create a user without any roles (no permissions)
        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $updateData = [
            'name' => 'Updated Name',
        ];

        // Act
        $response = $this->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(401);
    });

    it('can clear optional fields by setting them to null', function () {
        // Arrange
        $user = User::factory()->create([
            'bio' => 'Original bio',
            'twitter' => 'original_twitter',
        ]);
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $updateData = [
            'bio' => null,
            'twitter' => null,
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'bio' => null,
            'twitter' => null,
        ]);
    });

    it('can update social media links', function () {
        // Arrange
        $user = User::factory()->create();
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $updateData = [
            'twitter' => 'new_twitter',
            'facebook' => 'new_facebook',
            'linkedin' => 'new_linkedin',
            'github' => 'new_github',
        ];

        // Act
        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'twitter' => 'new_twitter',
            'facebook' => 'new_facebook',
            'linkedin' => 'new_linkedin',
            'github' => 'new_github',
        ]);
    });
});
