<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register a test route using the middleware
    Route::middleware('optional.sanctum')->get('/test-optional-auth', function () {
        return response()->json([
            'user_id' => optional(request()->user())->id,
        ]);
    });
});

test('authenticates user with valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token', ['access-api'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/test-optional-auth');

    $response->assertOk();
    $response->assertJson(['user_id' => $user->id]);
});

test('does not authenticate user without access_api ability', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token', ['other-ability'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/test-optional-auth');

    $response->assertOk();
    $response->assertJson(['user_id' => null]);
});

test('sets user resolver to null if no token', function () {
    $response = $this->getJson('/test-optional-auth');
    $response->assertOk();
    $response->assertJson(['user_id' => null]);
});

test('sets user resolver to null if token invalid', function () {
    $response = $this->withHeader('Authorization', 'Bearer invalidtoken')
        ->getJson('/test-optional-auth');
    $response->assertOk();
    $response->assertJson(['user_id' => null]);
});

test('allows request to proceed without authentication', function () {
    $response = $this->getJson('/test-optional-auth');
    $response->assertOk();
    $response->assertJson(['user_id' => null]);
});
