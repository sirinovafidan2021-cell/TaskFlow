<?php

namespace App\Providers;

use App\Enums\PermissionName;
use App\Models\User;
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
        Gate::define('viewDashboard', fn (User $user): bool => $user->hasPermissionTo(PermissionName::DashboardView->value));
    }
}
