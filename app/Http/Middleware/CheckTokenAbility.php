<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability = 'access-api'): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! $token->can($ability)) {
            return response()->apiError(
                __('Unauthorized. Invalid token or insufficient permissions.'),
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }
}
