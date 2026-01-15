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

    /**
     * Send password reset link to user's email.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function forgotPassword(string $email): void;

    /**
     * Reset user's password using a reset token.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function resetPassword(string $email, string $token, string $password): void;

    /**
     * Register a new user and return the user with access and refresh tokens.
     * The user object will have dynamically added 'access_token' and 'refresh_token' properties.
     */
    public function register(\App\Data\RegisterDTO $dto): User;
}
