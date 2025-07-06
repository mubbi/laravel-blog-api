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
     * The user object will have a dynamically added 'token' property.
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

        // Generate Sanctum token and attach to user dynamically
        $token = $user->createToken('auth_token')->plainTextToken;
        // @phpstan-ignore-next-line
        $user->token = $token;

        return $user;
    }
}
