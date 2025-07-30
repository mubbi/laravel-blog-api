<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        Model::shouldBeStrict(! $this->app->isProduction());
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Disable rate limiting during testing
        if ($this->app->environment('testing')) {
            RateLimiter::for('api', fn () => Limit::none());
        } else {
            // Rate Limiting for API routes
            RateLimiter::for('api', function (Request $request) {
                $rateLimit = config('rate-limiting.api.default_rate_limit');
                $rateLimitInt = is_numeric($rateLimit) ? (int) $rateLimit : 60;

                return Limit::perMinute($rateLimitInt)
                    ->by($request->user()?->id ?: $request->ip());
            });
        }
    }
}
