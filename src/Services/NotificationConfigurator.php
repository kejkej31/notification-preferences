<?php

namespace KejKej\NotificationPreferences\Services;

use KejKej\NotificationPreferences\Contracts\NotificationConfiguratorContract;

class NotificationConfigurator implements NotificationConfiguratorContract
{
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
