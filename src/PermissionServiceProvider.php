<?php

namespace Firebed\Permission;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerConfig();

        $this->registerMigrations();

        $this->registerPermissions();
    }

    private function registerConfig(): void
    {
        if (!function_exists('config_path')) {
            return;
        }

        $this->mergeConfigFrom(__DIR__ . '/config/permission.php', 'permission');

        $this->publishes([__DIR__ . '/config/permission.php' => config_path('permission.php')], 'permission-config');
    }

    private function registerMigrations(): void
    {
        if (!function_exists('database_path')) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        $this->publishes([__DIR__ . '/Database/migrations' => database_path('migrations')], 'permission-migrations');
    }

    /**
     * Register the permission check method on the gate.
     * We resolve the Gate fresh here, for benefit of long-running instances.
     */
    private function registerPermissions(): void
    {
        Gate::before(static function (Authorizable $user, string $ability) {
            if (method_exists($user, 'checkPermissionTo')) {
                return $user->checkPermissionTo($ability) ?: NULL;
            }
        });
    }
}