<?php

namespace KejKej\NotificationPreferences\Contracts;

use KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix;

interface HasNotificationPreferences
{
    public function getNotificationPreferences(): NotificationPreferencesMatrix;

    /**
     * Get explicitly selected channels for given notification.
     * Returns null if user has no preference saved for this notification.
     *
     * @param string $notificationName
     * @return array<int, string>|null
     */
    public function getEnabledChannelsForNotification(string $notificationName): ?array;
}