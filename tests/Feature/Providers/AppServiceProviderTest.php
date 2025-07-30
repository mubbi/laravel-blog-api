<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('sets rate limiting for api routes in non-testing environment', function () {
    // Backup the app
    $app = app();

    // Clear previously registered rate limiters
    RateLimiter::clear('api');

    // Fake a non-testing environment by mocking the environment() method
    $provider = new class($app) extends \App\Providers\AppServiceProvider
    {
        public function boot(): void
        {
            // Force a fake non-testing env
            $this->app->detectEnvironment(fn () => 'production');
            parent::boot();
        }
    };

    // Set desired rate limit value
    config()->set('rate-limiting.api.default_rate_limit', 123);

    // Boot the overridden provider
    $provider->boot();

    // Create a test request with a user
    $request = new Request;
    $request->setUserResolver(fn () => (object) ['id' => 42]);

    $limiter = RateLimiter::limiter('api');
    $limit = $limiter($request);

    expect($limit)->toBeInstanceOf(Limit::class);
    expect($limit->maxAttempts)->toBe(123);
    expect($limit->key)->toBe(42);

    // Create another request without a user
    $requestNoUser = new Request;
    $requestNoUser->server->set('REMOTE_ADDR', '127.0.0.1');

    $limitIp = $limiter($requestNoUser);
    expect($limitIp->key)->toBe('127.0.0.1');
});
