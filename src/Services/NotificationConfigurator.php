<?php

namespace KejKej\NotificationPreferences\Services;

use KejKej\NotificationPreferences\Contracts\NotificationConfigurator as NotificationConfiguratorContract;

class NotificationConfigurator implements NotificationConfiguratorContract
{
    protected static array $notificationPreferencesObject;

    public function notificationPreferencesObject(): array
    {
        if(isset(static::$notificationPreferencesObject)) {
            return static::$notificationPreferencesObject;
        }
        static::$notificationPreferencesObject = array_fill_keys(
            array_keys($this->notifications()), 
            array_fill_keys($this->channels(), false)
        );
        return static::$notificationPreferencesObject;
    }

    /**
     * Find a notification by its class name.
     *
     * @param string $name
     * @return string|null
     */
    public function findNotificationByClass(string $name): ?string
    {
        $notifications = $this->notifications();
        $key = array_search($name, $notifications, true);

        return $key !== false ? $key : null;
    }
    
    /**
     * Get the available notification types.
     *
     * @return array
     */
    public function notifications(): array
    {
        return config('notification-preferences.notifications', []);
    }

    /**
     * Get notification channels that should be used by default.
     *
     * @return array
     */
    public function defaultChannels(): array
    {
        return config('notification-preferences.default_channels', []);
    }
    
    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public function channels(): array
    {
        return config('notification-preferences.channels', []);
    }

    /**
     * Get all notification preferences configuration.
     *
     * @return array
     */
    public function all(): array
    {
        return [
            'channels' => $this->channels(),
            'default_channels' => $this->defaultChannels(),
            'notifications' => $this->notifications(),
        ];
    }
}
