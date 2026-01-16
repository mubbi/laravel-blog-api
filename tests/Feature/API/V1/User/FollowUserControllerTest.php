<?php

declare(strict_types=1);

use App\Events\User\UserFollowedEvent;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/FollowUserController', function () {
    it('can follow a user when authenticated and has permission', function () {
        Event::fake([UserFollowedEvent::class]);
        $follower = createUserWithPermission('follow_users');
        $userToFollow = User::factory()->create();

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('user.followed_successfully'))
            ->and($response->json('data'))->toBeNull()
            ->and($follower->following()->where('following_id', $userToFollow->id)->exists())->toBeTrue();

        Event::assertDispatched(UserFollowedEvent::class, fn ($event) => $event->follower->id === $follower->id && $event->followed->id === $userToFollow->id);
    });

    it('returns success message when already following', function () {
        $follower = createUserWithPermission('follow_users');
        $userToFollow = User::factory()->create();
        $follower->following()->attach($userToFollow->id);

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('user.already_following'));
    });

    it('returns 403 when trying to follow self', function () {
        $user = createUserWithPermission('follow_users');

        Sanctum::actingAs($user, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $user->id]));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $userToFollow = User::factory()->create();

        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have follow_users permission', function () {
        $follower = User::factory()->create();
        $userToFollow = User::factory()->create();

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.follow', ['user' => $userToFollow->id]));

        $response->assertStatus(403);
    });
});
