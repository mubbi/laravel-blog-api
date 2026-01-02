<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider
 *
 * Responsible for core application bootstrapping:
 * - Date/Time configuration
 * - Eloquent Model configuration
 * - Database safety settings
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use CarbonImmutable for date handling
        Date::use(CarbonImmutable::class);

        // Enable strict mode for Eloquent models in non-production environments
        // This helps catch lazy loading and other issues during development
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prohibit destructive database commands in production
        // This prevents accidental data loss from migrations or commands
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
