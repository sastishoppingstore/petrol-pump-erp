<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Auth\LoginThrottleService;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Global helpers (setting(), waghera) — composer autoload change
        // kiye baghair load karo taake har jagah available hon.
        require_once app_path('helpers.php');

        // Scalar constructor args are not auto-resolvable, so build these explicitly
        // from config.
        $this->app->singleton(LoginThrottleService::class, fn () => LoginThrottleService::fromConfig());
        
        // Settings service
        $this->app->singleton(\App\Services\Admin\SettingsService::class, function () {
            return new \App\Services\Admin\SettingsService();
        });
    }

    public function boot(): void
    {
        $this->registerPermissionGates();
    }

    /**
     * Every permission becomes a Gate ability, so both
     * Gate::allows('sales.create') and @can('sales.create') work, and both
     * resolve through User::hasPermission() — a single authorisation path.
     */
    private function registerPermissionGates(): void
    {
        foreach (PermissionList::allNames() as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}
