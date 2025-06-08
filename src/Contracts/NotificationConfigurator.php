<?php

namespace KejKej\NotificationPreferences\Contracts;

interface NotificationConfigurator
{
    /**
     * Get full notification preferences object.
     * This object contains all available notifications and channels,
     * with values set to false.
     *
     * @return string
     */
    public function notificationPreferencesObject(): array;

    /**
     * Find a notification by its class name.
     *
     * @param string $name
     * @return string|null
     */
    public function findNotificationByClass(string $name): ?string;

    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public function channels(): array;

    /**
     * Get the available notification types.
     *
     * @return array
     */
    public function notifications(): array;

    /**
     * Get notification channels that should be used by default
     *
     * @return array
     */
    public function defaultChannels(): array;

    /**
     * Get all notification preferences configuration.
     *
     * @return array
     */
    public function all(): array;
}
