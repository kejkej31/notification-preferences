<?php

namespace KejKej\NotificationPreferences;

use Illuminate\Support\ServiceProvider;

class NotificationPreferencesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/notification-preferences.php' => config_path('notification-preferences.php'),
        ], 'notification-preferences-config');

         $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations')
        ], 'notification-preferences-migrations');
    }

    public function register()
    {
        $this->app->singleton(
            \KejKej\NotificationPreferences\Contracts\NotificationConfigurator::class,
            \KejKej\NotificationPreferences\Services\NotificationConfigurator::class
        );
    }
}
