<?php

declare(strict_types=1);

use App\Events\Auth\UserLoggedInEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Mockery\MockInterface;

describe('API/V1/Auth/LoginController', function () {
    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('J{kj)6ig8x51'),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'J{kj)6ig8x51',
        ]);

        expect($response)->toHaveApiSuccessStructure();
    });

    it('returns 422 validation error when password is missing', function () {
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
    });

    it('returns 401 when AuthService throws UnauthorizedException', function () {
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('login')
                ->with('test@example.com', 'ValidPass123!')
                ->andThrow(new UnauthorizedException('Invalid credentials'));
        });

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'ValidPass123!',
        ]);

        expect($response)->toHaveApiErrorStructure(401)
            ->and($response->json('message'))->toBe(__('auth.failed'));
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('login')
                ->with('test@example.com', 'AnotherValid123!')
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'AnotherValid123!',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('can login and returns user with roles and permissions', function () {
        $permission1 = Permission::factory()->create(['slug' => 'test-read-'.uniqid()]);
        $permission2 = Permission::factory()->create(['slug' => 'test-write-'.uniqid()]);
        $role = Role::factory()->create(['slug' => 'test-editor-'.uniqid()]);
        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $user = User::factory()->create([
            'email' => 'testeditor@example.com',
            'password' => Hash::make('EditorPass123!'),
        ]);
        $user->roles()->attach($role->id);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'testeditor@example.com',
            'password' => 'EditorPass123!',
        ]);

        $responseData = $response->json('data');
        expect($response)->toHaveApiSuccessStructure()
            ->and($responseData['roles'])->toContain($role->slug)
            ->and($responseData['permissions'])->toContain($permission1->slug)
            ->and($responseData['permissions'])->toContain($permission2->slug);
    });

    it('dispatches UserLoggedInEvent when user logs in successfully', function () {
        Event::fake([UserLoggedInEvent::class]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestP@ss123'),
        ]);

        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'TestP@ss123',
        ]);

        expect($response)->toHaveApiSuccessStructure();
        Event::assertDispatched(UserLoggedInEvent::class, fn ($event) => $event->user->id === $user->id && $event->user->email === 'test@example.com');
    });
});
