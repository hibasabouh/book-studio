<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->info->title = 'Book Studio API';
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
