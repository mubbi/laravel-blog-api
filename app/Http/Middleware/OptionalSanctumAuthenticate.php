<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Middleware to optionally authenticate a user via Sanctum token.
 * Sets the user resolver to null if no valid token is present.
 */
class OptionalSanctumAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = null;

        $token = $request->bearerToken();
        if (is_string($token) && $token !== '') {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken !== null && $accessToken instanceof PersonalAccessToken) {
                $tokenable = $accessToken->tokenable;
                // Check for 'access-api' ability
                if ($tokenable instanceof User && $accessToken->can('access-api')) {
                    $user = $tokenable;
                }
            }
        }

        $request->setUserResolver(static fn (): ?User => $user);

        return $next($request);
    }
}
