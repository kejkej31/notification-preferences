<?php

namespace KejKej\NotificationPreferences\Contracts;

interface HasChannels
{
    /**
     * Get the list of channels this notification can be sent on.
     *
     * @return array<int, string>
     */
    public function getAvailableChannels(): array;

    /**
     * Get the list of channels that should be enabled by default for this notification.
     *
     * @return array<int, string>
     */
    public function getDefaultChannels(): array;
}
