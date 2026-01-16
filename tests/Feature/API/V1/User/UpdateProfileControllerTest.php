<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/User/UpdateProfileController', function () {
    it('can update user profile with valid data', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $updateData = [
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'twitter' => 'updated_twitter',
            'website' => 'https://example.com',
        ];

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'bio',
            'twitter',
            'website',
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
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $originalName = $user->name;
        $updateData = ['bio' => 'Only updating bio'];

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $originalName,
            'bio' => 'Only updating bio',
        ]);
    });

    it('validates URL fields', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $updateData = [
            'avatar_url' => 'not-a-url',
            'website' => 'invalid-url',
        ];

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'avatar_url' => ['The avatar url field must be a valid URL.'],
                    'website' => ['The website field must be a valid URL.'],
                ],
            ]);
    });

    it('validates string length limits', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $updateData = [
            'name' => str_repeat('a', 300),
            'bio' => str_repeat('b', 1500),
        ];

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                    'bio' => ['The bio field must not be greater than 1000 characters.'],
                ],
            ]);
    });

    it('returns 403 when user lacks edit_profile permission', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->putJson(route('api.v1.user.profile.update'), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    });

    it('can clear optional fields by setting them to null', function () {
        $user = User::factory()->create([
            'bio' => 'Original bio',
            'twitter' => 'original_twitter',
        ]);
        $subscriberRole = Role::where('name', UserRole::SUBSCRIBER->value)->first();
        attachRoleAndRefreshCache($user, $subscriberRole);

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), [
                'bio' => null,
                'twitter' => null,
            ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'bio' => null,
            'twitter' => null,
        ]);
    });

    it('can update social media links', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $updateData = [
            'twitter' => 'new_twitter',
            'facebook' => 'new_facebook',
            'linkedin' => 'new_linkedin',
            'github' => 'new_github',
        ];

        $response = $this->actingAs($user)
            ->putJson(route('api.v1.user.profile.update'), $updateData);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'twitter' => 'new_twitter',
            'facebook' => 'new_facebook',
            'linkedin' => 'new_linkedin',
            'github' => 'new_github',
        ]);
    });
});
