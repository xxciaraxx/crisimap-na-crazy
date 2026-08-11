<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-users', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('manage-settings', fn ($user) => $user->hasRole('super_admin'));
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->latest()->take(8)->get() : collect());
        });
    }
}
