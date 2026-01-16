<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;

describe('API/V1/Auth/ResetPasswordController', function () {
    it('successfully resets password with valid token', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = \Illuminate\Support\Str::random(64);
        $table = config('auth.passwords.users.table', 'password_reset_tokens');

        DB::table($table)->insert([
            'email' => 'test@example.com',
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => $token,
            'password' => 'NewSecureP@ssw0rd123',
            'password_confirmation' => 'NewSecureP@ssw0rd123',
        ]);

        expect($response)->toHaveApiSuccessStructure()
            ->and($response->json('message'))->toBe(__('passwords.reset'))
            ->and($response->json('data'))->toBeNull();

        // Verify password was updated
        $user->refresh();
        expect(Hash::check('NewSecureP@ssw0rd123', $user->password))->toBeTrue();
        expect(Hash::check('OldPassword123!', $user->password))->toBeFalse();
    });

    it('returns 422 validation error when email is missing', function () {
        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'token' => 'some-token',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure([
                'error' => [
                    'email',
                ],
            ]);
    });

    it('returns 422 validation error when token is missing', function () {
        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure([
                'error' => [
                    'token',
                ],
            ]);
    });

    it('returns 422 validation error when password is missing', function () {
        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure([
                'error' => [
                    'password',
                ],
            ]);
    });

    it('returns 422 validation error when password confirmation does not match', function () {
        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password' => 'NewPass123!',
            'password_confirmation' => 'DifferentPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure([
                'error' => [
                    'password',
                ],
            ]);
    });

    it('returns 422 validation error when password does not meet requirements', function () {
        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
            ])
            ->assertJsonStructure([
                'error' => [
                    'password',
                ],
            ]);
    });

    it('returns 422 when AuthService throws ValidationException for invalid token', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('resetPassword')
                ->with('test@example.com', 'invalid-token', 'TestP@ssw0rd2024!')
                ->once()
                ->andThrow(ValidationException::withMessages([
                    'token' => [__('passwords.token')],
                ]));
        });

        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'password' => 'TestP@ssw0rd2024!',
            'password_confirmation' => 'TestP@ssw0rd2024!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'token' => [__('passwords.token')],
                ],
            ]);
    });

    it('returns 422 when AuthService throws ValidationException for non-existent user', function () {
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('resetPassword')
                ->with('nonexistent@example.com', 'some-token', 'TestP@ssw0rd2024!')
                ->once()
                ->andThrow(ValidationException::withMessages([
                    'email' => [__('passwords.user')],
                ]));
        });

        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'nonexistent@example.com',
            'token' => 'some-token',
            'password' => 'TestP@ssw0rd2024!',
            'password_confirmation' => 'TestP@ssw0rd2024!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'error' => [
                    'email' => [__('passwords.user')],
                ],
            ]);
    });

    it('returns 500 when AuthService throws unexpected exception', function () {
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('resetPassword')
                ->with('test@example.com', 'some-token', 'TestP@ssw0rd2024!')
                ->once()
                ->andThrow(new \RuntimeException(__('common.database_connection_failed')));
        });

        $response = $this->postJson(route('api.v1.auth.password.reset'), [
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password' => 'TestP@ssw0rd2024!',
            'password_confirmation' => 'TestP@ssw0rd2024!',
        ]);

        expect($response)->toHaveApiErrorStructure(500)
            ->and($response->json('message'))->toBe(__('common.something_went_wrong'));
    });
});
