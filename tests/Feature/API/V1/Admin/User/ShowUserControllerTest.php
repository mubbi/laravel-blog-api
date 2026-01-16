<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

describe('API/V1/Admin/User/ShowUserController', function () {
    it('can show a user with full details', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'banned_at' => null,
            'blocked_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $user));

        expect($response)->toHaveApiSuccessStructure([
            'id', 'name', 'email', 'email_verified_at', 'banned_at', 'blocked_at',
            'status', 'created_at', 'updated_at',
            'roles' => [
                '*' => [
                    'id', 'name', 'display_name',
                ],
            ],
        ])->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.name'))->toBe('John Doe')
            ->and($response->json('data.email'))->toBe('john@example.com')
            ->and($response->json('data.banned_at'))->toBeNull()
            ->and($response->json('data.blocked_at'))->toBeNull();
    });

    it('can show a banned user', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $bannedUser = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $bannedUser));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($bannedUser->id)
            ->and($response->json('data.banned_at'))->not->toBeNull()
            ->and($response->json('data.blocked_at'))->toBeNull();
    });

    it('can show a blocked user', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $blockedUser = User::factory()->create([
            'banned_at' => null,
            'blocked_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $blockedUser));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($blockedUser->id)
            ->and($response->json('data.banned_at'))->toBeNull()
            ->and($response->json('data.blocked_at'))->not->toBeNull();
    });

    it('can show a user with both banned and blocked status', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create([
            'banned_at' => now()->subDays(5),
            'blocked_at' => now()->subDays(2),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $user));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.banned_at'))->not->toBeNull()
            ->and($response->json('data.blocked_at'))->not->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 401 when user is not authenticated', function () {
        $user = User::factory()->create();

        $response = $this->getJson(route('api.v1.admin.users.show', $user));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUser();
        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $targetUser));

        $response->assertStatus(403);
    });

    it('includes user roles in response', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        attachRoleAndRefreshCache($user, $authorRole);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $user));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.roles'))->toHaveCount(1)
            ->and($response->json('data.roles.0.id'))->toBe($authorRole->id)
            ->and($response->json('data.roles.0.name'))->toBe(UserRole::AUTHOR->value)
            ->and($response->json('data.roles.0.display_name'))->toBe(UserRole::AUTHOR->displayName());
    });

    it('handles users with multiple roles', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create();
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();
        $editorRole = Role::where('name', UserRole::EDITOR->value)->first();
        $user->roles()->attach([$authorRole->id, $editorRole->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $user));

        expect($response->getStatusCode())->toBe(200);
        $responseData = $response->json('data.roles');
        expect($responseData)->toHaveCount(2);
        $roleNames = collect($responseData)->pluck('name')->toArray();
        expect(in_array(UserRole::AUTHOR->value, $roleNames))->toBeTrue()
            ->and(in_array(UserRole::EDITOR->value, $roleNames))->toBeTrue();
    });

    it('handles users with no roles', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $user));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($user->id)
            ->and($response->json('data.roles'))->toBe([]);
    });

    it('includes email verification status', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $verifiedUser));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($verifiedUser->id)
            ->and($response->json('data.email_verified_at'))->not->toBeNull();
    });

    it('handles unverified email users', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->getJson(route('api.v1.admin.users.show', $unverifiedUser));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->json('data.id'))->toBe($unverifiedUser->id)
            ->and($response->json('data.email_verified_at'))->toBeNull();
    });
});
