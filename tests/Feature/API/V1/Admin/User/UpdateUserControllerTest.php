<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserUpdatedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/UpdateUserController', function () {
    it('can update a user successfully', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        expect($response)->toHaveApiSuccessStructure([
            'id', 'name', 'email', 'email_verified_at', 'banned_at', 'blocked_at',
            'status', 'created_at', 'updated_at',
            'roles' => [
                '*' => [
                    'id', 'name', 'display_name',
                ],
            ],
        ])->and($response->json('data.id'))->toBe($userToUpdate->id)
            ->and($response->json('data.name'))->toBe('New Name')
            ->and($response->json('data.email'))->toBe('new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    it('can update only name', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'test@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'test@example.com',
        ]);
    });

    it('can update only email', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create([
            'name' => 'Test User',
            'email' => 'old@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'email' => 'new@example.com',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Test User',
            'email' => 'new@example.com',
        ]);
    });

    it('returns 404 when user does not exist', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', 99999), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 401 when user is not authenticated', function () {
        $userToUpdate = User::factory()->create();

        $response = $this->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have permission', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::SUBSCRIBER->value);
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(403);
    });

    it('validates email format', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email field must be a valid email address.'],
                ],
            ]);
    });

    it('validates email uniqueness', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        User::factory()->create(['email' => 'existing@example.com']);
        $userToUpdate = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
    });

    it('allows updating to same email', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'test@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'test@example.com',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'New Name',
            'email' => 'test@example.com',
        ]);
    });

    it('validates name is required when provided', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => '',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'name' => ['The name field is required.'],
                ],
            ]);
    });

    it('validates name length', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => str_repeat('a', 256),
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                ],
            ]);
    });

    it('maintains other user properties when updating', function () {
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'email_verified_at' => now(),
            'banned_at' => now()->subDays(5),
            'blocked_at' => now()->subDays(2),
        ];
        $userToUpdate = User::factory()->create($originalData);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $userToUpdate->refresh();
        expect($userToUpdate->name)->toBe('New Name')
            ->and($userToUpdate->email)->toBe('new@example.com')
            ->and($userToUpdate->email_verified_at->toDateTimeString())->toBe($originalData['email_verified_at']->toDateTimeString())
            ->and($userToUpdate->banned_at->toDateTimeString())->toBe($originalData['banned_at']->toDateTimeString())
            ->and($userToUpdate->blocked_at->toDateTimeString())->toBe($originalData['blocked_at']->toDateTimeString());
    });

    it('allows admin to update themselves', function () {
        $admin = User::factory()->create([
            'name' => 'Old Admin Name',
            'email' => 'admin@example.com',
        ]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);
        $token = $admin->createToken('test-token', ['access-api']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token->plainTextToken,
        ])->putJson(route('api.v1.admin.users.update', $admin), [
            'name' => 'New Admin Name',
            'email' => 'newadmin@example.com',
        ]);

        expect($response->getStatusCode())->toBe(200);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'New Admin Name',
            'email' => 'newadmin@example.com',
        ]);
    });

    it('dispatches UserUpdatedEvent when user is updated', function () {
        Event::fake([UserUpdatedEvent::class]);
        $auth = createAuthenticatedUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUpdate = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$auth['tokenString'],
        ])->putJson(route('api.v1.admin.users.update', $userToUpdate), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(UserUpdatedEvent::class, fn ($event) => $event->user->id === $userToUpdate->id
            && $event->user->name === 'New Name'
            && $event->user->email === 'new@example.com');
    });
});
