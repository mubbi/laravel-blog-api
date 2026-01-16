<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Laravel\Sanctum\PersonalAccessToken;

describe('App\Services\Auth\AuthService tests', function () {
    beforeEach(function () {
        $this->authService = app(AuthService::class);
    });

    describe('login', function () {
        it('successfully logs in user with valid credentials and creates tokens', function () {
            // Create user with roles and permissions
            $permission = Permission::factory()->create(['slug' => 'test-permission-'.uniqid()]);
            $role = Role::factory()->create(['slug' => 'test-role-'.uniqid()]);
            $role->permissions()->attach($permission->id);

            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
            ]);
            $user->roles()->attach($role->id);

            // Create some existing tokens to test revocation
            $user->createToken('old-token', ['access-api']);

            $result = $this->authService->login('test@example.com', 'password123');

            expect($result)->toBeInstanceOf(User::class);
            expect($result->email)->toBe('test@example.com');
            expect($result->access_token)->toBeString();
            expect($result->refresh_token)->toBeString();
            expect($result->access_token_expires_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);
            expect($result->refresh_token_expires_at)->toBeInstanceOf(\Carbon\CarbonInterface::class);

            // Verify relationships are loaded
            expect($result->relationLoaded('roles'))->toBeTrue();
            expect($result->roles->first()->relationLoaded('permissions'))->toBeTrue();

            // Verify old tokens were revoked
            expect($user->fresh()->tokens()->count())->toBe(2); // Only new access + refresh tokens
        });

        it('throws UnauthorizedException when user does not exist', function () {
            expect(fn () => $this->authService->login('nonexistent@example.com', 'password123'))
                ->toThrow(UnauthorizedException::class, __('auth.failed'));
        });

        it('throws UnauthorizedException when password is incorrect', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => Hash::make('correct-password'),
            ]);

            expect(fn () => $this->authService->login('test@example.com', 'wrong-password'))
                ->toThrow(UnauthorizedException::class, __('auth.failed'));
        });
    });

    describe('refreshToken', function () {
        it('successfully refreshes access token with valid refresh token', function () {
            // Create user with roles and permissions
            $permission = Permission::factory()->create(['slug' => 'refresh-permission-'.uniqid()]);
            $role = Role::factory()->create(['slug' => 'refresh-role-'.uniqid()]);
            $role->permissions()->attach($permission->id);

            $user = User::factory()->create();
            $user->roles()->attach($role->id);

            // Create valid refresh token
            $refreshTokenExpiration = now()->addDays(30);
            $refreshToken = $user->createToken(
                'refresh_token',
                ['refresh-token'],
                $refreshTokenExpiration
            );

            // Create some access tokens to test deletion
            $user->createToken('access_token_1', ['access-api']);
            $user->createToken('access_token_2', ['access-api']);

            $result = $this->authService->refreshToken($refreshToken->plainTextToken);

            expect($result)->toBeInstanceOf(User::class);
            expect($result->access_token)->toBeString();
            expect($result->refresh_token)->toBe($refreshToken->plainTextToken);
            expect($result->access_token_expires_at)->toBeInstanceOf(CarbonInterface::class);
            expect($result->refresh_token_expires_at)->toBeInstanceOf(CarbonInterface::class);

            // Verify relationships are loaded
            expect($result->relationLoaded('roles'))->toBeTrue();
            expect($result->roles->first()->relationLoaded('permissions'))->toBeTrue();

            // Verify old access tokens were deleted but refresh token remains
            $remainingTokens = $user->fresh()->tokens;
            expect($remainingTokens->count())->toBe(2); // refresh + new access token

            // Check that one token has refresh-token ability
            $hasRefreshToken = $remainingTokens->contains(function ($token) {
                return in_array('refresh-token', $token->abilities);
            });
            expect($hasRefreshToken)->toBeTrue();
        });

        it('throws UnauthorizedException when refresh token does not exist', function () {
            expect(fn () => $this->authService->refreshToken('invalid-token'))
                ->toThrow(UnauthorizedException::class, __('auth.invalid_refresh_token'));
        });

        it('throws UnauthorizedException when token lacks refresh-token ability', function () {
            $user = User::factory()->create();
            $accessToken = $user->createToken('access_token', ['access-api']);

            expect(fn () => $this->authService->refreshToken($accessToken->plainTextToken))
                ->toThrow(UnauthorizedException::class, __('auth.invalid_refresh_token'));
        });

        it('throws UnauthorizedException and deletes expired refresh token', function () {
            $user = User::factory()->create();

            // Create expired refresh token
            $expiredToken = $user->createToken(
                'refresh_token',
                ['refresh-token'],
                now()->subMinutes(1) // Already expired
            );

            expect(fn () => $this->authService->refreshToken($expiredToken->plainTextToken))
                ->toThrow(UnauthorizedException::class, __('auth.refresh_token_expired'));

            // Verify expired token was deleted
            expect(PersonalAccessToken::find($expiredToken->accessToken->id))->toBeNull();
        });
    });

    describe('logout', function () {
        it('revokes all user tokens', function () {
            $user = User::factory()->create();

            // Create multiple tokens
            $user->createToken('token1', ['access-api']);
            $user->createToken('token2', ['refresh-token']);
            $user->createToken('token3', ['access-api']);

            expect($user->tokens()->count())->toBe(3);

            $this->authService->logout($user);

            expect($user->fresh()->tokens()->count())->toBe(0);
        });
    });

    describe('forgotPassword', function () {
        it('sends password reset email to existing user', function () {
            \Illuminate\Support\Facades\Mail::fake();

            $user = User::factory()->create([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            $this->authService->forgotPassword('test@example.com');

            \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PasswordResetMail::class, function ($mail) {
                return $mail->hasTo('test@example.com')
                    && $mail->token !== null
                    && $mail->name === 'Test User';
            });

            // Verify token was stored in database
            $this->assertDatabaseHas('password_reset_tokens', [
                'email' => 'test@example.com',
            ]);
        });

        it('does not send email for non-existent user to prevent enumeration', function () {
            \Illuminate\Support\Facades\Mail::fake();

            $this->authService->forgotPassword('nonexistent@example.com');

            \Illuminate\Support\Facades\Mail::assertNothingSent();

            // Verify no token was stored
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => 'nonexistent@example.com',
            ]);
        });

        it('deletes existing token before creating new one', function () {
            \Illuminate\Support\Facades\Mail::fake();

            $user = User::factory()->create([
                'email' => 'test@example.com',
            ]);

            // Create an existing token
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
                'email' => 'test@example.com',
                'token' => \Illuminate\Support\Facades\Hash::make('old-token'),
                'created_at' => now(),
            ]);

            $this->authService->forgotPassword('test@example.com');

            // Should only have one token (the new one)
            $tokenCount = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', 'test@example.com')
                ->count();

            expect($tokenCount)->toBe(1);
        });
    });

    describe('resetPassword', function () {
        it('successfully resets password with valid token', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => Hash::make('old-password'),
            ]);

            // Create a valid reset token
            $token = \Illuminate\Support\Str::random(64);
            $table = config('auth.passwords.users.table', 'password_reset_tokens');

            \Illuminate\Support\Facades\DB::table($table)->insert([
                'email' => 'test@example.com',
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            // Create some tokens to verify they get revoked
            $user->createToken('token1', ['access-api']);
            $user->createToken('token2', ['refresh-token']);

            $this->authService->resetPassword('test@example.com', $token, 'NewSecureP@ssw0rd123');

            // Verify password was updated
            $user->refresh();
            expect(Hash::check('NewSecureP@ssw0rd123', $user->password))->toBeTrue();
            expect(Hash::check('old-password', $user->password))->toBeFalse();

            // Verify token was deleted
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => 'test@example.com',
            ]);

            // Verify all user tokens were revoked
            expect($user->fresh()->tokens()->count())->toBe(0);
        });

        it('throws ValidationException when user does not exist', function () {
            expect(fn () => $this->authService->resetPassword('nonexistent@example.com', 'token', 'NewPass123!'))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('throws ValidationException when token does not exist', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
            ]);

            expect(fn () => $this->authService->resetPassword('test@example.com', 'invalid-token', 'NewPass123!'))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });

        it('throws ValidationException when token is expired', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
            ]);

            $token = \Illuminate\Support\Str::random(64);
            $table = config('auth.passwords.users.table', 'password_reset_tokens');

            // Create expired token (more than 60 minutes ago)
            \Illuminate\Support\Facades\DB::table($table)->insert([
                'email' => 'test@example.com',
                'token' => Hash::make($token),
                'created_at' => now()->subHours(2),
            ]);

            expect(fn () => $this->authService->resetPassword('test@example.com', $token, 'NewPass123!'))
                ->toThrow(\Illuminate\Validation\ValidationException::class);

            // Verify expired token was deleted
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => 'test@example.com',
            ]);
        });

        it('throws ValidationException when token is invalid', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
            ]);

            $table = config('auth.passwords.users.table', 'password_reset_tokens');

            // Create token with different hash
            \Illuminate\Support\Facades\DB::table($table)->insert([
                'email' => 'test@example.com',
                'token' => Hash::make('different-token'),
                'created_at' => now(),
            ]);

            expect(fn () => $this->authService->resetPassword('test@example.com', 'wrong-token', 'NewPass123!'))
                ->toThrow(\Illuminate\Validation\ValidationException::class);
        });
    });

    describe('register', function () {
        it('successfully registers a new user with default subscriber role and creates tokens', function () {
            // Ensure subscriber role exists
            $subscriberRole = \App\Models\Role::firstOrCreate(
                ['name' => \App\Enums\UserRole::SUBSCRIBER->value],
                ['slug' => 'subscriber']
            );

            $dto = new \App\Data\User\RegisterDTO(
                name: 'John Doe',
                email: 'john@example.com',
                password: 'TestRegP@ss2024!',
                bio: 'Test bio',
                twitter: '@johndoe',
            );

            $result = $this->authService->register($dto);

            expect($result)->toBeInstanceOf(User::class);
            expect($result->name)->toBe('John Doe');
            expect($result->email)->toBe('john@example.com');
            expect($result->bio)->toBe('Test bio');
            expect($result->twitter)->toBe('@johndoe');
            expect($result->access_token)->toBeString();
            expect($result->refresh_token)->toBeString();
            expect($result->access_token_expires_at)->toBeInstanceOf(CarbonInterface::class);
            expect($result->refresh_token_expires_at)->toBeInstanceOf(CarbonInterface::class);

            // Verify user was created in database
            $this->assertDatabaseHas('users', [
                'email' => 'john@example.com',
                'name' => 'John Doe',
            ]);

            // Verify password was hashed
            $user = User::where('email', 'john@example.com')->first();
            expect($user)->not->toBeNull();
            expect(Hash::check('TestRegP@ss2024!', $user->password))->toBeTrue();

            // Verify subscriber role was assigned
            expect($user->roles->pluck('slug')->toArray())->toContain('subscriber');

            // Verify relationships are loaded
            expect($result->relationLoaded('roles'))->toBeTrue();
            expect($result->roles->first()->relationLoaded('permissions'))->toBeTrue();

            // Verify tokens were created
            expect($user->fresh()->tokens()->count())->toBe(2); // access + refresh tokens
        });

        it('successfully registers user with all optional fields', function () {
            // Ensure subscriber role exists
            $subscriberRole = \App\Models\Role::firstOrCreate(
                ['name' => \App\Enums\UserRole::SUBSCRIBER->value],
                ['slug' => 'subscriber']
            );

            $dto = new \App\Data\User\RegisterDTO(
                name: 'Jane Doe',
                email: 'jane@example.com',
                password: 'TestRegP@ss2024!',
                avatarUrl: 'https://example.com/avatar.jpg',
                bio: 'Software developer',
                twitter: '@jane',
                facebook: 'jane.doe',
                linkedin: 'jane-doe',
                github: 'jane-doe',
                website: 'https://jane.example.com',
            );

            $result = $this->authService->register($dto);

            expect($result)->toBeInstanceOf(User::class);
            expect($result->avatar_url)->toBe('https://example.com/avatar.jpg');
            expect($result->bio)->toBe('Software developer');
            expect($result->twitter)->toBe('@jane');
            expect($result->facebook)->toBe('jane.doe');
            expect($result->linkedin)->toBe('jane-doe');
            expect($result->github)->toBe('jane-doe');
            expect($result->website)->toBe('https://jane.example.com');

            // Verify all fields were saved
            $this->assertDatabaseHas('users', [
                'email' => 'jane@example.com',
                'avatar_url' => 'https://example.com/avatar.jpg',
                'bio' => 'Software developer',
                'twitter' => '@jane',
                'facebook' => 'jane.doe',
                'linkedin' => 'jane-doe',
                'github' => 'jane-doe',
                'website' => 'https://jane.example.com',
            ]);
        });

        it('successfully registers user even when subscriber role does not exist', function () {
            // Don't create subscriber role - test graceful handling

            $dto = new \App\Data\User\RegisterDTO(
                name: 'Test User',
                email: 'test@example.com',
                password: 'TestRegP@ss2024!',
            );

            $result = $this->authService->register($dto);

            expect($result)->toBeInstanceOf(User::class);
            expect($result->email)->toBe('test@example.com');

            // User should still be created even if role assignment fails
            $this->assertDatabaseHas('users', [
                'email' => 'test@example.com',
            ]);
        });

        it('dispatches UserCreatedEvent and UserLoggedInEvent when user registers', function () {
            \Illuminate\Support\Facades\Event::fake([\App\Events\User\UserCreatedEvent::class, \App\Events\Auth\UserLoggedInEvent::class]);

            // Ensure subscriber role exists
            $subscriberRole = \App\Models\Role::firstOrCreate(
                ['name' => \App\Enums\UserRole::SUBSCRIBER->value],
                ['slug' => 'subscriber']
            );

            $dto = new \App\Data\User\RegisterDTO(
                name: 'John Doe',
                email: 'john@example.com',
                password: 'TestRegP@ss2024!',
            );

            $result = $this->authService->register($dto);

            \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\User\UserCreatedEvent::class, function ($event) {
                return $event->user->email === 'john@example.com';
            });

            \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\Auth\UserLoggedInEvent::class, function ($event) {
                return $event->user->email === 'john@example.com';
            });
        });

        it('creates tokens with correct abilities', function () {
            // Ensure subscriber role exists
            $subscriberRole = \App\Models\Role::firstOrCreate(
                ['name' => \App\Enums\UserRole::SUBSCRIBER->value],
                ['slug' => 'subscriber']
            );

            $dto = new \App\Data\User\RegisterDTO(
                name: 'John Doe',
                email: 'john@example.com',
                password: 'TestRegP@ss2024!',
            );

            $result = $this->authService->register($dto);

            $user = User::where('email', 'john@example.com')->first();
            $tokens = $user->fresh()->tokens;

            // Should have 2 tokens: access and refresh
            expect($tokens->count())->toBe(2);

            // Check access token has correct ability
            $accessToken = $tokens->firstWhere('name', 'access_token');
            expect($accessToken)->not->toBeNull();
            expect($accessToken->abilities)->toContain('access-api');

            // Check refresh token has correct ability
            $refreshToken = $tokens->firstWhere('name', 'refresh_token');
            expect($refreshToken)->not->toBeNull();
            expect($refreshToken->abilities)->toContain('refresh-token');
        });
    });
});
