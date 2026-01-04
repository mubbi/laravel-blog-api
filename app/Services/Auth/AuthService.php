<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Events\Auth\TokenRefreshedEvent;
use App\Events\Auth\UserLoggedInEvent;
use App\Events\Auth\UserLoggedOutEvent;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

final class AuthService implements AuthServiceInterface
{
    private const PASSWORD_RESET_TABLE = 'password_reset_tokens';

    private const DEFAULT_TOKEN_EXPIRY_MINUTES = 60;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Attempt to authenticate a user and return the user if successful.
     * The user object will have dynamically added 'access_token' and 'refresh_token' properties.
     *
     * @throws UnauthorizedException
     */
    public function login(string $email, string $password): User
    {
        $user = $this->userRepository->query()
            ->with(['roles.permissions'])
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new UnauthorizedException(__('auth.failed'));
        }

        $user->tokens()->delete();

        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        $this->attachTokensToUser($user, $accessToken, $refreshToken);

        Event::dispatch(new UserLoggedInEvent($user));

        return $user;
    }

    /**
     * Refresh the access token using a valid refresh token.
     *
     * @throws UnauthorizedException
     */
    public function refreshToken(string $refreshToken): User
    {
        // Find the refresh token
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($refreshToken);

        if (! $token || ! $token instanceof \Laravel\Sanctum\PersonalAccessToken || ! $token->can('refresh-token')) {
            throw new UnauthorizedException(__('auth.invalid_refresh_token'));
        }

        $user = null;
        if ($token->tokenable instanceof User) {
            $user = $token->tokenable;
        }
        if (! $user) {
            throw new UnauthorizedException(__('auth.invalid_refresh_token'));
        }

        // Check if refresh token is expired
        /** @var Carbon|null $tokenExpiresAt */
        $tokenExpiresAt = $token->expires_at;
        if ($tokenExpiresAt && $tokenExpiresAt->isPast()) {
            $token->delete();
            throw new UnauthorizedException(__('auth.refresh_token_expired'));
        }

        $user->tokens()->where('abilities', 'like', '%access-api%')->delete();

        $accessToken = $this->createAccessToken($user);
        $user->load(['roles.permissions']);

        $this->attachTokensToUser($user, $accessToken, $refreshToken, $tokenExpiresAt);

        Event::dispatch(new TokenRefreshedEvent($user));

        return $user;
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();

        Event::dispatch(new UserLoggedOutEvent($user));
    }

    /**
     * Send password reset link to user's email.
     *
     * @throws ValidationException
     */
    public function forgotPassword(string $email): void
    {
        $user = $this->userRepository->query()
            ->where('email', $email)
            ->first();

        // Always return success to prevent user enumeration
        if (! $user) {
            return;
        }

        $token = Str::random(64);
        $table = $this->getPasswordResetTable();

        DB::table($table)->where('email', $email)->delete();
        DB::table($table)->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        Mail::to($email)->send(
            new PasswordResetMail(
                email: $email,
                token: $token,
                name: $user->name
            )
        );
    }

    /**
     * Reset user's password using a reset token.
     *
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $user = $this->userRepository->query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.user')],
            ]);
        }

        $table = $this->getPasswordResetTable();
        $tokenRecord = DB::table($table)->where('email', $email)->first();

        if (! $tokenRecord || ! $this->isTokenValid($tokenRecord, $token, $table, $email)) {
            throw ValidationException::withMessages([
                'token' => [__('passwords.token')],
            ]);
        }

        DB::transaction(function () use ($user, $password, $email, $table): void {
            $user->password = Hash::make($password);
            $user->save();
            DB::table($table)->where('email', $email)->delete();
            $user->tokens()->delete();
        });
    }

    /**
     * Create an access token for the user
     */
    private function createAccessToken(User $user): \Laravel\Sanctum\NewAccessToken
    {
        $expiration = now()->addMinutes($this->getAccessTokenExpirationMinutes());

        return $user->createToken('access_token', ['access-api'], $expiration);
    }

    /**
     * Create a refresh token for the user
     */
    private function createRefreshToken(User $user): \Laravel\Sanctum\NewAccessToken
    {
        $expiration = now()->addMinutes($this->getRefreshTokenExpirationMinutes());

        return $user->createToken('refresh_token', ['refresh-token'], $expiration);
    }

    /**
     * Attach tokens to user object dynamically
     */
    private function attachTokensToUser(
        User $user,
        \Laravel\Sanctum\NewAccessToken $accessToken,
        \Laravel\Sanctum\NewAccessToken|string $refreshToken,
        ?Carbon $refreshTokenExpiresAt = null
    ): void {
        $user->access_token = $accessToken->plainTextToken;
        /** @var Carbon|null $accessTokenExpiresAt */
        $accessTokenExpiresAt = $accessToken->accessToken->expires_at;
        $user->access_token_expires_at = $accessTokenExpiresAt;

        if ($refreshToken instanceof \Laravel\Sanctum\NewAccessToken) {
            $user->refresh_token = $refreshToken->plainTextToken;
            /** @var Carbon|null $refreshTokenExpiresAtFromToken */
            $refreshTokenExpiresAtFromToken = $refreshToken->accessToken->expires_at;
            $user->refresh_token_expires_at = $refreshTokenExpiresAtFromToken;
        } else {
            $user->refresh_token = $refreshToken;
            $user->refresh_token_expires_at = $refreshTokenExpiresAt;
        }
    }

    /**
     * Get access token expiration in minutes
     */
    private function getAccessTokenExpirationMinutes(): int
    {
        /** @var mixed $config */
        $config = config('sanctum.access_token_expiration');

        return (int) ($config ?? 15);
    }

    /**
     * Get refresh token expiration in minutes
     */
    private function getRefreshTokenExpirationMinutes(): int
    {
        /** @var mixed $config */
        $config = config('sanctum.refresh_token_expiration');

        return (int) ($config ?? 43200);
    }

    /**
     * Get password reset table name
     */
    private function getPasswordResetTable(): string
    {
        /** @var mixed $config */
        $config = config('auth.passwords.users.table', self::PASSWORD_RESET_TABLE);

        return is_string($config) ? $config : self::PASSWORD_RESET_TABLE;
    }

    /**
     * Get password reset token expiration in minutes
     */
    private function getPasswordResetExpirationMinutes(): int
    {
        /** @var mixed $config */
        $config = config('auth.passwords.users.expire', self::DEFAULT_TOKEN_EXPIRY_MINUTES);

        return (int) ($config ?? self::DEFAULT_TOKEN_EXPIRY_MINUTES);
    }

    /**
     * Validate password reset token
     */
    private function isTokenValid(object $tokenRecord, string $token, string $table, string $email): bool
    {
        if (! isset($tokenRecord->token, $tokenRecord->created_at)) {
            return false;
        }

        /** @var string $createdAtString */
        $createdAtString = $tokenRecord->created_at;
        $createdAt = Carbon::parse($createdAtString);

        if (now()->diffInMinutes($createdAt) > $this->getPasswordResetExpirationMinutes()) {
            DB::table($table)->where('email', $email)->delete();

            return false;
        }

        /** @var string $hashedToken */
        $hashedToken = $tokenRecord->token;

        return Hash::check($token, $hashedToken);
    }
}
