<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Facades\Gate;
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
        // Admin-only access, used via the `can:admin` middleware and @can checks.
        Gate::define('admin', fn (User $user) => $user->isAdmin());

        // Document the Sanctum bearer token so "Try it" in the API docs is authenticated.
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->info->title = 'Book Studio API';
                $openApi->secure(SecurityScheme::http('bearer'));
            })
            // Document the X-Locale header read by SetLocale on every endpoint.
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo) {
                $operation->addParameters([
                    Parameter::make('X-Locale', 'header')
                        ->setSchema(Schema::fromType((new StringType)->enum(config('app.supported_locales', ['en']))))
                        ->description('Response language. Falls back to the app default if omitted or unsupported.')
                        ->example('en'),
                ]);
            });
    }
}
