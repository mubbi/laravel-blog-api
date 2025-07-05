<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('API/V1/Auth/LoginController', function () {
    it('can login with valid credentials', function () {
        // Create a test user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('J{kj)6ig8x51'),
        ]);

        // Attempt login
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'J{kj)6ig8x51',
        ]);

        // Check response
        $response->assertStatus(200);
    });

    it('returns 422 validation error when password is missing', function () {
        // Attempt login with only email
        $response = $this->postJson(route('api.v1.auth.login'), [
            'email' => 'test@example.com',
        ]);

        // Check response
        $response->assertStatus(422);
    });
});
