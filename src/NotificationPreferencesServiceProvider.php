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
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
