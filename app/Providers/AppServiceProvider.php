<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use App\Models\Beneficiary;
use App\Models\User;
use App\Models\DocumentRecord;
use App\Models\GeneralIntakeSheet;
use App\Policies\DocumentRecordPolicy;
use App\Policies\GeneralIntakeSheetPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(GeneralIntakeSheet::class, GeneralIntakeSheetPolicy::class);
        Gate::policy(DocumentRecord::class, DocumentRecordPolicy::class);
        RateLimiter::for('nominatim', fn () => Limit::perSecond(1)->by('nominatim'));
        Gate::define('manage-users', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('manage-settings', fn ($user) => $user->hasRole('super_admin'));
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->latest()->take(8)->get() : collect());
            $badges = [];
            if ($user?->role === 'admin') $badges['pending_applications'] = Beneficiary::active()->where('status', 'Pending')->count();
            if ($user?->role === 'super_admin') $badges['pending_users'] = User::where('status', 'pending')->count();
            $view->with('navigationBadges', $badges);
        });
    }
}
