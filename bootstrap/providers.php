<?php

return [
    // Core application bootstrapping
    App\Providers\AppServiceProvider::class,

    // Repository bindings (needed early for dependency injection)
    App\Providers\RepositoryServiceProvider::class,

    // Authentication and authorization
    App\Providers\AuthServiceProvider::class,

    // Event listeners and observers
    App\Providers\EventServiceProvider::class,

    // Rate limiting configuration
    App\Providers\RateLimitServiceProvider::class,

    // Response macros
    App\Providers\ResponseServiceProvider::class,

    // API documentation
    App\Providers\ScrambleServiceProvider::class,
];
