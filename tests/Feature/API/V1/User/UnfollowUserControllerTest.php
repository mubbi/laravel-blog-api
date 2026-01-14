<?php

declare(strict_types=1);

use App\Events\User\UserUnfollowedEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/UnfollowUserController', function () {
    it('can unfollow a user when authenticated and has permission', function () {
        // Arrange
        Event::fake([UserUnfollowedEvent::class]);
        $follower = User::factory()->create();
        $userToUnfollow = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'unfollow_users'],
            ['slug' => 'unfollow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $follower->roles()->attach($role->id);
        $follower->refresh();
        $follower->load('roles.permissions');

        // Create existing follow relationship
        $follower->following()->attach($userToUnfollow->id);

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('user.unfollowed_successfully'),
                'data' => null,
            ]);

        // Verify follow relationship was removed
        expect($follower->following()->where('following_id', $userToUnfollow->id)->exists())->toBeFalse();

        // Verify event was dispatched
        Event::assertDispatched(UserUnfollowedEvent::class, function ($event) use ($follower, $userToUnfollow) {
            return $event->follower->id === $follower->id && $event->unfollowed->id === $userToUnfollow->id;
        });
    });

    it('returns success message when not following', function () {
        // Arrange
        $follower = User::factory()->create();
        $userToUnfollow = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'unfollow_users'],
            ['slug' => 'unfollow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $follower->roles()->attach($role->id);
        $follower->refresh();
        $follower->load('roles.permissions');

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('user.not_following'),
                'data' => null,
            ]);
    });

    it('returns 403 when trying to unfollow self', function () {
        // Arrange
        $user = User::factory()->create();

        // Get or create permission and role
        $permission = Permission::firstOrCreate(
            ['name' => 'unfollow_users'],
            ['slug' => 'unfollow_users']
        );
        $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
        $role->permissions()->attach($permission->id);
        $user->roles()->attach($role->id);
        $user->refresh();
        $user->load('roles.permissions');

        // Act
        Sanctum::actingAs($user, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $user->id]));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_unfollow_self'),
                'data' => null,
            ]);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToUnfollow = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        // Assert
        $response->assertStatus(401);
    });

    it('returns 403 when user does not have unfollow_users permission', function () {
        // Arrange
        $follower = User::factory()->create();
        $userToUnfollow = User::factory()->create();

        // Act
        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        // Assert
        $response->assertStatus(403);
    });
});
