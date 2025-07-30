<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ScrambleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', function (User $user) {
            return app()->isLocal() || $user->hasRole(UserRole::ADMINISTRATOR->value);
        });

        Scramble::registerApi('v1', [
            'api_path' => 'api/v1',
        ]);

        Scramble::configure()
            ->routes(function (Route $route): bool {
                return Str::startsWith($route->uri, 'api/');
            })
            ->withDocumentTransformers(function (mixed $openApi): void {
                if ($openApi instanceof OpenApi) {
                    $openApi->secure(
                        SecurityScheme::http('bearer')
                    );
                }
            });
    }
}
