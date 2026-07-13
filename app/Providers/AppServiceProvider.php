<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', function ($user) {
            return in_array($user->role, ['mswdo_head', 'technical_expert'], true);
        });

        Gate::define('manage-settings', function ($user) {
            return in_array($user->role, ['mswdo_head', 'technical_expert'], true);
        });
    }
}
