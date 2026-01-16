<?php

declare(strict_types=1);

use App\Events\Auth\UserLoggedOutEvent;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;

describe('API/V1/Auth/LogoutController', function () {
    it('can logout successfully with valid authenticated user', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Sanctum::actingAs($user, ['access-api']);

        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('logout')
                ->with($user)
                ->once()
                ->andReturnNull();
        });

        $response = $this->postJson(route('api.v1.auth.logout'));

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('auth.logout_success'))
            ->and($response->json('data'))->toBeNull();
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);

        Sanctum::actingAs($user, ['access-api']);

        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('logout')
                ->with($user)
                ->once()
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        $response = $this->postJson(route('api.v1.auth.logout'));

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });

    it('dispatches UserLoggedOutEvent when user logs out successfully', function () {
        Event::fake([UserLoggedOutEvent::class]);
        $user = User::factory()->create(['email' => 'test@example.com']);

        Sanctum::actingAs($user, ['access-api']);

        $response = $this->postJson(route('api.v1.auth.logout'));

        expect($response->getStatusCode())->toBe(200);
        Event::assertDispatched(UserLoggedOutEvent::class, fn ($event) => $event->user->id === $user->id);
    });
});
