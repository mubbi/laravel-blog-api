<?php

declare(strict_types=1);

use App\Events\User\UserFollowedEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/FollowUserController', function () {
    it('can follow a user when authenticated and has permission', function () {
        // Arrange
        Event::fake([UserFollowedEvent::class]);
        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'follow_users'],
            ['slug' => 'follow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $follower->roles()->attach($role->id);
        $follower->refresh();
        $follower->load('roles.permissions');

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('user.followed_successfully'),
                'data' => null,
            ]);

        // Verify follow relationship was created
        expect($follower->following()->where('following_id', $userToFollow->id)->exists())->toBeTrue();

        // Verify event was dispatched
        Event::assertDispatched(UserFollowedEvent::class, function ($event) use ($follower, $userToFollow) {
            return $event->follower->id === $follower->id && $event->followed->id === $userToFollow->id;
        });
    });

    it('returns success message when already following', function () {
        // Arrange
        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'follow_users'],
            ['slug' => 'follow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $follower->roles()->attach($role->id);
        $follower->refresh();
        $follower->load('roles.permissions');

        // Create existing follow relationship
        $follower->following()->attach($userToFollow->id);

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('user.already_following'),
                'data' => null,
            ]);
    });

    it('returns 403 when trying to follow self', function () {
        // Arrange
        $user = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'follow_users'],
            ['slug' => 'follow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
        $user->refresh();
        $user->load('roles.permissions');

        // Act
        Sanctum::actingAs($user, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $user->id]));

        // Assert
        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToFollow = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have follow_users permission', function () {
        // Arrange
        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        // Assert
        $response->assertStatus(403);
    });
});
