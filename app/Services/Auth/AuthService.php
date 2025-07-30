<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Interfaces\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;

final class AuthService implements AuthServiceInterface
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
        /** @var mixed $accessTokenExpirationConfig */
        $accessTokenExpirationConfig = config('sanctum.access_token_expiration');
        $accessTokenExpiration = now()->addMinutes((int) ($accessTokenExpirationConfig ?? 15));
        $accessToken = $user->createToken(
            'access_token',
            ['access-api'],
            $accessTokenExpiration
        );

        // Generate refresh token (30 days)
        /** @var mixed $refreshTokenExpirationConfig */
        $refreshTokenExpirationConfig = config('sanctum.refresh_token_expiration');
        $refreshTokenExpiration = now()->addMinutes((int) ($refreshTokenExpirationConfig ?? 43200));
        $refreshToken = $user->createToken(
            'refresh_token',
            ['refresh-token'],
            $refreshTokenExpiration
        );

        // Attach tokens to user dynamically
        $user->access_token = $accessToken->plainTextToken;
        $user->refresh_token = $refreshToken->plainTextToken;
        $user->access_token_expires_at = $accessTokenExpiration;
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
        /** @var \Illuminate\Support\Carbon|null $tokenExpiresAt */
        $tokenExpiresAt = $token->expires_at;
        if ($tokenExpiresAt && $tokenExpiresAt->isPast()) {
            $token->delete();
            throw new UnauthorizedException(__('auth.refresh_token_expired'));
        }

        // Revoke all access tokens (keep refresh tokens)
        $user->tokens()->where('abilities', 'like', '%access-api%')->delete();

        // Generate new access token
        /** @var mixed $accessTokenExpirationConfig */
        $accessTokenExpirationConfig = config('sanctum.access_token_expiration');
        $accessTokenExpiration = now()->addMinutes((int) ($accessTokenExpirationConfig ?? 15));
        $accessToken = $user->createToken(
            'access_token',
            ['access-api'],
            $accessTokenExpiration
        );

        // Load relationships
        $user->load(['roles.permissions']);

        // Attach new access token to user dynamically
        $user->access_token = $accessToken->plainTextToken;
        $user->refresh_token = $refreshToken;
        $user->access_token_expires_at = $accessTokenExpiration;
        $user->refresh_token_expires_at = $tokenExpiresAt;

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
