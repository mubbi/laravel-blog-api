<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;

final class AuthService
{
    /**
     * Attempt to authenticate a user and return the user if successful.
     * The user object will have dynamically added 'access_token' and 'refresh_token' properties.
     *
     * @throws UnauthorizedException
     */
    public function login(string $email, string $password): User
    {
        $user = User::with(['roles.permissions'])
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new UnauthorizedException(__('auth.failed'));
        }

        // Revoke all existing tokens for this user
        $user->tokens()->delete();

        // Generate access token (15 minutes)
        $accessTokenExpiration = now()->addMinutes((int) (config('sanctum.access_token_expiration') ?? 15));
        $accessToken = $user->createToken(
            'access_token',
            ['access-api'],
            $accessTokenExpiration
        );

        // Generate refresh token (30 days)
        $refreshTokenExpiration = now()->addMinutes((int) (config('sanctum.refresh_token_expiration') ?? 43200));
        $refreshToken = $user->createToken(
            'refresh_token',
            ['refresh-token'],
            $refreshTokenExpiration
        );

        // Attach tokens to user dynamically
        // @phpstan-ignore-next-line
        $user->access_token = $accessToken->plainTextToken;
        // @phpstan-ignore-next-line
        $user->refresh_token = $refreshToken->plainTextToken;
        // @phpstan-ignore-next-line
        $user->access_token_expires_at = $accessTokenExpiration;
        // @phpstan-ignore-next-line
        $user->refresh_token_expires_at = $refreshTokenExpiration;

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

        if (! $token || ! $token->can('refresh-token')) {
            throw new UnauthorizedException(__('auth.invalid_refresh_token'));
        }

        /** @var User $user */
        $user = $token->tokenable;

        // Check if refresh token is expired
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            throw new UnauthorizedException(__('auth.refresh_token_expired'));
        }

        // Revoke all access tokens (keep refresh tokens)
        $user->tokens()->where('abilities', 'like', '%access-api%')->delete();

        // Generate new access token
        $accessTokenExpiration = now()->addMinutes((int) (config('sanctum.access_token_expiration') ?? 15));
        $accessToken = $user->createToken(
            'access_token',
            ['access-api'],
            $accessTokenExpiration
        );

        // Load relationships
        $user->load(['roles.permissions']);

        // Attach new access token to user dynamically
        // @phpstan-ignore-next-line
        $user->access_token = $accessToken->plainTextToken;
        // @phpstan-ignore-next-line
        $user->refresh_token = $refreshToken;
        // @phpstan-ignore-next-line
        $user->access_token_expires_at = $accessTokenExpiration;
        // @phpstan-ignore-next-line
        $user->refresh_token_expires_at = $token->expires_at;

        return $user;
    }

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
