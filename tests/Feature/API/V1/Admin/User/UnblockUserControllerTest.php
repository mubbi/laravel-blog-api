<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserUnblockedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/User/UnblockUserController', function () {
    it('can unblock a user successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'blocked_at',
            'status',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUnblock->id,
            'blocked_at' => null,
        ]);

        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('can unblock a user who is also banned', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull()
            ->and($userToUnblock->banned_at)->not->toBeNull();
    });

    it('can unblock an already unblocked user (no effect)', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create(['blocked_at' => null]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 403 when user lacks unblock_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        $response = $this->postJson(route('api.v1.users.unblock', $userToUnblock));

        $response->assertStatus(401);
    });

    it('prevents admin from unblocking themselves', function () {
        $admin = User::factory()->create(['blocked_at' => now()]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $admin));

        expect($response->getStatusCode())->toBe(403)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.cannot_unblock_self'));

        $admin->refresh();
        expect($admin->blocked_at)->not->toBeNull();
    });

    it('maintains other user properties when unblocking', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'banned_at' => now()->subDays(2),
        ];
        $userToUnblock = User::factory()->create(array_merge($originalData, [
            'blocked_at' => now(),
        ]));

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull()
            ->and($userToUnblock->name)->toBe($originalData['name'])
            ->and($userToUnblock->email)->toBe($originalData['email'])
            ->and($userToUnblock->email_verified_at->toDateTimeString())->toBe($originalData['email_verified_at']->toDateTimeString())
            ->and($userToUnblock->banned_at->toDateTimeString())->toBe($originalData['banned_at']->toDateTimeString());
    });

    it('handles users with long block history', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create(['blocked_at' => now()->subMonths(3)]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('can unblock user with recent block', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create(['blocked_at' => now()->subHours(2)]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        $userToUnblock->refresh();
        expect($userToUnblock->blocked_at)->toBeNull();
    });

    it('dispatches UserUnblockedEvent when user is unblocked', function () {
        Event::fake([UserUnblockedEvent::class]);
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnblock = User::factory()->create(['blocked_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unblock', $userToUnblock));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(UserUnblockedEvent::class, fn ($event) => $event->user->id === $userToUnblock->id
            && $event->user->blocked_at === null);
    });
});
