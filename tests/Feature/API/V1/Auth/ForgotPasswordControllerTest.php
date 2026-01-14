<?php

declare(strict_types=1);

use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;

describe('API/V1/Auth/ForgotPasswordController', function () {
    it('sends password reset email for valid email', function () {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $response = $this->postJson(route('api.v1.auth.password.forgot'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
            ])
            ->assertJson([
                'status' => true,
                'message' => __('passwords.sent'),
                'data' => null,
            ]);

        Mail::assertSent(PasswordResetMail::class, function ($mail) {
            return $mail->hasTo('test@example.com')
                && $mail->name === 'Test User'
                && $mail->token !== null;
        });
    });

    it('returns success even for non-existent email to prevent user enumeration', function () {
        Mail::fake();

        $response = $this->postJson(route('api.v1.auth.password.forgot'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => __('passwords.sent'),
                'data' => null,
            ]);

        Mail::assertNothingSent();
    });

    it('returns 422 validation error when email is missing', function () {
        $response = $this->postJson(route('api.v1.auth.password.forgot'), []);

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

    it('returns 422 validation error when email format is invalid', function () {
        $response = $this->postJson(route('api.v1.auth.password.forgot'), [
            'email' => 'invalid-email',
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

    it('returns 500 when AuthService throws unexpected exception', function () {
        $this->mock(AuthServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('forgotPassword')
                ->with('test@example.com')
                ->once()
                ->andThrow(new \Exception(__('common.database_connection_failed')));
        });

        $response = $this->postJson(route('api.v1.auth.password.forgot'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => __('common.something_went_wrong'),
                'data' => null,
                'error' => null,
            ]);
    });
});
