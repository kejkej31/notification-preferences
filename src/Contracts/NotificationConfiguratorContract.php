<?php

namespace KejKej\NotificationPreferences\Contracts;

interface NotificationConfiguratorContract
{
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
     * Get all notification preferences configuration.
     *
     * @return array
     */
    public function all(): array;
}
