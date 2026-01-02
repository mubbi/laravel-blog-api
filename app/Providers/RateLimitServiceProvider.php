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
        } else {
            // Rate Limiting for API routes
            RateLimiter::for('api', function (Request $request) {
                $rateLimit = config('rate-limiting.api.default_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 60;

                return Limit::perMinute($rateLimitInt)
                    ->by($request->user()?->id ?: Helper::getRealIpAddress($request));
            });
        }
    }
}
