<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Adds security headers to all HTTP responses to protect against common attacks:
 * - XSS Protection
 * - Clickjacking Protection
 * - MIME Type Sniffing Protection
 * - Referrer Policy
 * - Content Security Policy
 * - HSTS (HTTP Strict Transport Security) - if HTTPS
 */
final class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // X-Frame-Options: Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', true);

        // X-Content-Type-Options: Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff', true);

        // X-XSS-Protection: Enable browser XSS filter (legacy, but still useful)
        $response->headers->set('X-XSS-Protection', '1; mode=block', true);

        // Referrer-Policy: Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', true);

        // Content-Security-Policy: Restrict resource loading
        // For API, we can be more restrictive since we're not serving HTML
        $csp = "default-src 'self'; script-src 'none'; style-src 'none'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none';";
        $response->headers->set('Content-Security-Policy', $csp, true);

        // Permissions-Policy: Restrict browser features
        $permissionsPolicy = 'geolocation=(), microphone=(), camera=()';
        $response->headers->set('Permissions-Policy', $permissionsPolicy, true);

        // HSTS: Force HTTPS (only if already on HTTPS)
        if ($request->secure() && ! app()->environment('local')) {
            $maxAge = 31536000; // 1 year
            $response->headers->set(
                'Strict-Transport-Security',
                "max-age={$maxAge}; includeSubDomains; preload",
                true
            );
        }

        return $response;
    }
}
