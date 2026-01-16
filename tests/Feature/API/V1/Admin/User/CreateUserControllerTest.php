<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserCreatedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/CreateUserController', function () {
    it('can create a new user with valid data', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'bio' => 'A test user bio',
                'twitter' => 'johndoe',
                'role_id' => $authorRole->id,
            ]);

        expect($response->getStatusCode())->toBe(201)
            ->and($response)->toHaveApiSuccessStructure([
                'id',
                'name',
                'email',
                'bio',
                'twitter',
                'roles',
                'status',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        expect($user->roles)->toHaveCount(1)
            ->and($user->roles->first()->name)->toBe(UserRole::AUTHOR->value);
    });

    it('can create a user without optional fields', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ]);

        expect($response->getStatusCode())->toBe(201);
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    });

    it('validates required fields', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), []);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The name field is required. (and 2 more errors)',
                'data' => null,
                'error' => [
                    'name' => ['The name field is required.'],
                    'email' => ['The email field is required.'],
                    'password' => ['The password field is required.'],
                ],
            ]);
    });

    it('validates email uniqueness', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The email has already been taken.',
                'data' => null,
                'error' => [
                    'email' => ['The email has already been taken.'],
                ],
            ]);
    });

    it('validates password minimum length', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => '123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The password field must be at least 8 characters.',
                'data' => null,
                'error' => [
                    'password' => ['The password field must be at least 8 characters.'],
                ],
            ]);
    });

    it('validates URL fields', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'avatar_url' => 'not-a-url',
                'website' => 'invalid-url',
            ]);

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

    it('validates role_id exists', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'role_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'The selected role id is invalid.',
                'data' => null,
                'error' => [
                    'role_id' => ['The selected role id is invalid.'],
                ],
            ]);
    });

    it('returns 403 when user lacks create_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson(route('api.v1.admin.users.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    });

    it('dispatches UserCreatedEvent when user is created', function () {
        Event::fake([UserCreatedEvent::class]);
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $authorRole = Role::where('name', UserRole::AUTHOR->value)->first();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role_id' => $authorRole->id,
            ]);

        expect($response->getStatusCode())->toBe(201);
        Event::assertDispatched(UserCreatedEvent::class, fn ($event) => $event->user->email === 'newuser@example.com'
            && $event->user->name === 'New User');
    });
});
