<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserBannedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/Admin/User/BanUserController', function () {
    it('can ban a user successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToBan = User::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $userToBan));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'banned_at',
            'status',
        ]);

        $this->assertDatabaseHas('users', ['id' => $userToBan->id]);
        $userToBan->refresh();
        expect($userToBan->banned_at)->not->toBeNull();
    });

    it('can ban an already blocked user', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToBan = User::factory()->create(['blocked_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $userToBan));

        expect($response->getStatusCode())->toBe(200);
        $userToBan->refresh();
        expect($userToBan->banned_at)->not->toBeNull()
            ->and($userToBan->blocked_at)->not->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 403 when user lacks ban_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $userToBan = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.admin.users.ban', $userToBan));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToBan = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.admin.users.ban', $userToBan));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from banning themselves', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $admin));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_ban_self'),
            ]);
    });

    it('dispatches UserBannedEvent when user is banned', function () {
        // Arrange
        Event::fake([UserBannedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToBan = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.admin.users.ban', $userToBan));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserBannedEvent::class, function ($event) use ($userToBan) {
            return $event->user->id === $userToBan->id
                && $event->user->banned_at !== null;
        });
    });
});
