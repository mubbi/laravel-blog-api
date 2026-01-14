<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
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

        // Add global database query logging for non-production environments
        // This helps identify N+1 queries and slow queries during development
        if (! $this->app->isProduction()) {
            DB::listen(function (QueryExecuted $query): void {
                \Illuminate\Support\Facades\Log::debug('Database Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            });
        }
    }
}
