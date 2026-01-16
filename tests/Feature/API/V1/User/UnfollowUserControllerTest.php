<?php

declare(strict_types=1);

use App\Events\User\UserUnfollowedEvent;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

describe('API/V1/User/UnfollowUserController', function () {
    it('can unfollow a user when authenticated and has permission', function () {
        Event::fake([UserUnfollowedEvent::class]);
        $follower = createUserWithPermission('unfollow_users');
        $userToUnfollow = User::factory()->create();
        $follower->following()->attach($userToUnfollow->id);

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('user.unfollowed_successfully'))
            ->and($response->json('data'))->toBeNull()
            ->and($follower->following()->where('following_id', $userToUnfollow->id)->exists())->toBeFalse();

        Event::assertDispatched(UserUnfollowedEvent::class, fn ($event) => $event->follower->id === $follower->id && $event->unfollowed->id === $userToUnfollow->id);
    });

    it('returns success message when not following', function () {
        $follower = createUserWithPermission('unfollow_users');
        $userToUnfollow = User::factory()->create();

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('user.not_following'));
    });

    it('returns 403 when trying to unfollow self', function () {
        $user = createUserWithPermission('unfollow_users');

        Sanctum::actingAs($user, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $user->id]));

        expect($response->getStatusCode())->toBe(403)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.cannot_unfollow_self'));
    });

    it('returns 401 when not authenticated', function () {
        $userToUnfollow = User::factory()->create();

        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        $response->assertStatus(401);
    });

    it('returns 403 when user does not have unfollow_users permission', function () {
        $follower = User::factory()->create();
        $userToUnfollow = User::factory()->create();

        Sanctum::actingAs($follower, ['access-api']);
        $response = $this->postJson(route('api.v1.users.unfollow', ['user' => $userToUnfollow->id]));

        $response->assertStatus(403);
    });
});
