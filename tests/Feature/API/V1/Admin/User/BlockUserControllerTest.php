<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Events\User\UserBlockedEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Event;

describe('API/V1/User/BlockUserController', function () {
    it('can block a user successfully', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToBlock = User::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', $userToBlock));

        expect($response)->toHaveApiSuccessStructure([
            'id',
            'name',
            'email',
            'blocked_at',
            'status',
        ]);

        $this->assertDatabaseHas('users', ['id' => $userToBlock->id]);
        $userToBlock->refresh();
        expect($userToBlock->blocked_at)->not->toBeNull();
    });

    it('can block an already banned user', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);
        $userToBlock = User::factory()->create(['banned_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', $userToBlock));

        expect($response->getStatusCode())->toBe(200);
        $userToBlock->refresh();
        expect($userToBlock->blocked_at)->not->toBeNull()
            ->and($userToBlock->banned_at)->not->toBeNull();
    });

    it('returns 404 when user does not exist', function () {
        $admin = createUserWithRole(UserRole::ADMINISTRATOR->value);

        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', 99999));

        expect($response->getStatusCode())->toBe(404)
            ->and($response->json('status'))->toBeFalse()
            ->and($response->json('message'))->toBe(__('common.user_not_found'));
    });

    it('returns 403 when user lacks block_users permission', function () {
        $user = createUserWithRole(UserRole::SUBSCRIBER->value);
        $userToBlock = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('api.v1.users.block', $userToBlock));

        $response->assertStatus(403);
    });

    it('returns 401 when not authenticated', function () {
        // Arrange
        $userToBlock = User::factory()->create();

        // Act
        $response = $this->postJson(route('api.v1.users.block', $userToBlock));

        // Assert
        $response->assertStatus(401);
    });

    it('prevents admin from blocking themselves', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', $admin));

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => __('common.cannot_block_self'),
            ]);
    });

    it('maintains other user properties when blocking', function () {
        // Arrange
        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $originalData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => now(),
            'banned_at' => null,
        ];

        $userToBlock = User::factory()->create($originalData);

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', $userToBlock));

        // Assert
        $response->assertStatus(200);

        $userToBlock->refresh();
        $this->assertNotNull($userToBlock->blocked_at);
        $this->assertEquals($originalData['name'], $userToBlock->name);
        $this->assertEquals($originalData['email'], $userToBlock->email);
        $this->assertEquals($originalData['email_verified_at']->toDateTimeString(), $userToBlock->email_verified_at->toDateTimeString());
        $this->assertEquals($originalData['banned_at'], $userToBlock->banned_at);
    });

    it('dispatches UserBlockedEvent when user is blocked', function () {
        // Arrange
        Event::fake([UserBlockedEvent::class]);

        $admin = User::factory()->create();
        $adminRole = Role::where('name', UserRole::ADMINISTRATOR->value)->first();
        attachRoleAndRefreshCache($admin, $adminRole);

        $userToBlock = User::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->postJson(route('api.v1.users.block', $userToBlock));

        // Assert
        $response->assertStatus(200);

        Event::assertDispatched(UserBlockedEvent::class, function ($event) use ($userToBlock) {
            return $event->user->id === $userToBlock->id
                && $event->user->blocked_at !== null;
        });
    });
});
