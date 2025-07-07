<?php

declare(strict_types=1);

use App\Http\Middleware\CheckTokenAbility;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

describe('CheckTokenAbility Middleware', function () {
    it('allows request when user has valid token with required ability', function () {
        // Create a real user and token with the required ability
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['access-api'])->accessToken;

        // Create a real request and set the user with token
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        // Set the current access token for the user
        $user->withAccessToken($token);

        $middleware = new CheckTokenAbility;
        $next = fn ($request) => response()->json(['success' => true]);

        $response = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(200);
    });

    it('returns 401 when user has no token', function () {
        // Create request with user but no token
        $user = User::factory()->create();

        $request = $this->mock(Request::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('user')
                ->andReturn($user);
            $mock->shouldReceive('user->currentAccessToken')
                ->andReturn(null);
        });

        $middleware = new CheckTokenAbility;
        $next = fn ($request) => response()->json(['success' => true]);

        $response = $middleware->handle($request, $next);

        expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    });

    it('returns 401 when token lacks required ability', function () {
        // Create a real user and token without the required ability
        $user = User::factory()->create();
        $token = $user->createToken('test-token', ['basic-access'])->accessToken;

        // Create a real request and set the user with token
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        // Set the current access token for the user
        $user->withAccessToken($token);

        $middleware = new CheckTokenAbility;
        $next = fn ($request) => response()->json(['success' => true]);

        // Test with ability the token doesn't have
        $response = $middleware->handle($request, $next, 'admin-access');

        expect($response->getStatusCode())->toBe(Response::HTTP_UNAUTHORIZED);
    });
});
