<?php

namespace KejKej\NotificationPreferences\Services;

use KejKej\NotificationPreferences\Contracts\NotificationConfiguratorContract;

class NotificationConfigurator implements NotificationConfiguratorContract
{
    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public function channels(): array
    {
        return config('notification-settings.channels', []);
    }

    /**
     * Get the available notification types.
     *
     * @return array
     */
    public function notifiations(): array
    {
        return config('notification-settings.notifications', []);
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
            'notifications' => $this->notifiations(),
        ];
    }
}
