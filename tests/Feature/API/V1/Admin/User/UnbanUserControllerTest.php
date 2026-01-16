<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserUnbannedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/User/UnbanUserController', function () {
    it('can unban a user successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnban = User::factory()->create(['banned_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'banned_at',
            'status',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUnban->id,
            'banned_at' => null,
        ]);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('can unban a user who is also blocked', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToUnban = User::factory()->create([
            'banned_at' => now(),
            'blocked_at' => now(),
        ]);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        // Assert
        $response->assertStatus(200);

        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
        expect($userToUnban->blocked_at)->not->toBeNull(); // Should remain blocked
    });

    it('can unban an already unbanned user (no effect)', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnban = User::factory()->create(['banned_at' => null]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        expect($response->getStatusCode())->toBe(200);
        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 403 when user lacks unban_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $userToUnban = User::factory()->create(['banned_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        $userToUnban = User::factory()->create(['banned_at' => now()]);

        $response = $this->postJson(route('api.v1.users.unban', $userToUnban));

        $response->assertStatus(401);
    });

    it('prevents admin from unbanning themselves', function () {
        $admin = User::factory()->create(['banned_at' => now()]);
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $admin));

        expect($response->getStatusCode())->toBe(403)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.cannot_unban_self'));

        $admin->refresh();
        expect($admin->banned_at)->not->toBeNull();
    });

    it('maintains other user properties when unbanning', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'blocked_at' => now()->subDays(2),
        ];
        $userToUnban = User::factory()->create(array_merge($originalData, [
            'banned_at' => now(),
        ]));

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        expect($response->getStatusCode())->toBe(200);
        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull()
            ->and($userToUnban->name)->toBe($originalData['name'])
            ->and($userToUnban->email)->toBe($originalData['email'])
            ->and($userToUnban->email_verified_at->toDateTimeString())->toBe($originalData['email_verified_at']->toDateTimeString())
            ->and($userToUnban->blocked_at->toDateTimeString())->toBe($originalData['blocked_at']->toDateTimeString());
    });

    it('handles users with long ban history', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnban = User::factory()->create(['banned_at' => now()->subMonths(6)]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        expect($response->getStatusCode())->toBe(200);
        $userToUnban->refresh();
        expect($userToUnban->banned_at)->toBeNull();
    });

    it('dispatches UserUnbannedEvent when user is unbanned', function () {
        Event::fake([UserUnbannedEvent::class]);
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToUnban = User::factory()->create(['banned_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.unban', $userToUnban));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(UserUnbannedEvent::class, fn ($event) => $event->user->id === $userToUnban->id
            && $event->user->banned_at === null);
    });
});
