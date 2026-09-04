<?php

namespace App\Providers;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Services\NotificationCenterService;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Repositories\UserRepository::class, \App\Repositories\EloquentUserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('taskflow-api', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('taskflow-token', function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        Gate::define('viewDashboard', fn (User $user): bool => $user->hasPermissionTo(PermissionName::DashboardView->value));
        Gate::define('manageUsers', fn (User $user): bool => $user->hasRole(UserRole::Admin->value)
            && $user->hasPermissionTo(PermissionName::UserRolesManage->value));
        View::composer('components.workspace-header', fn ($view) => $view->with('unreadNotificationCount', auth()->check() ? app(NotificationCenterService::class)->unreadCount(auth()->user()) : 0));
    }
}
