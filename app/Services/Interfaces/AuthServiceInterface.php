<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Models\User;
use Illuminate\Validation\UnauthorizedException;

interface AuthServiceInterface
{
    /**
     * Attempt to authenticate a user and return the user if successful.
     * The user object will have dynamically added 'access_token' and 'refresh_token' properties.
     *
     * @throws UnauthorizedException
     */
    public function login(string $email, string $password): User;

    /**
     * Refresh the access token using a valid refresh token.
     *
     * @throws UnauthorizedException
     */
    public function refreshToken(string $refreshToken): User;

    /**
     * Logout user by revoking all tokens.
     */
    public function logout(User $user): void;
}
