<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Helper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Rate Limit Service Provider
 *
 * Responsible for configuring rate limiting for the application.
 * This includes API rate limiting and any other rate limiting policies.
 */
final class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap rate limiting services.
     */
    public function boot(): void
    {
        // Disable rate limiting during testing
        if ($this->app->environment('testing')) {
            RateLimiter::for('api', fn () => Limit::none());
            RateLimiter::for('auth', fn () => Limit::none());
            RateLimiter::for('sensitive', fn () => Limit::none());
            RateLimiter::for('admin', fn () => Limit::none());
        } else {
            // Default API rate limiting (IP-based or user-based)
            RateLimiter::for('api', function (Request $request) {
                $rateLimit = config('rate-limiting.api.default_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 60;

                return Limit::perMinute($rateLimitInt)
                    ->by($request->user()?->id ?: Helper::getRealIpAddress($request));
            });

            // Stricter rate limiting for authentication endpoints (IP-based)
            RateLimiter::for('auth', function (Request $request) {
                $rateLimit = config('rate-limiting.api.auth_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 5;

                // Always use IP address for auth endpoints to prevent brute force
                return Limit::perMinute($rateLimitInt)
                    ->by(Helper::getRealIpAddress($request));
            });

            // Stricter rate limiting for sensitive operations (user or IP-based)
            RateLimiter::for('sensitive', function (Request $request) {
                $rateLimit = config('rate-limiting.api.sensitive_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 10;

                return Limit::perMinute($rateLimitInt)
                    ->by($request->user()?->id ?: Helper::getRealIpAddress($request));
            });

            // Rate limiting for admin endpoints (user-based)
            RateLimiter::for('admin', function (Request $request) {
                $rateLimit = config('rate-limiting.api.admin_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 30;

                // Admin endpoints should be user-based
                $key = $request->user()?->id;
                if ($key === null) {
                    // Fallback to IP if no user (shouldn't happen but safety first)
                    $key = Helper::getRealIpAddress($request);
                }

                return Limit::perMinute($rateLimitInt)->by($key);
            });
        }
    }
}
