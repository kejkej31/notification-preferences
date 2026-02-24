<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\NotificationConfigurator;

trait RoutesNotificationsViaPreferences
{
    /**
     * Get the notification channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $manager = app(NotificationConfigurator::class);

        if (! is_object($notifiable) || ! method_exists($notifiable, 'getEnabledChannelsForNotification')) {
            return $manager->defaultChannelsForNotification($this);
        }

        $notificationName = $manager->findNotificationByClass($this::class);
        if ($notificationName === null) {
            return $manager->defaultChannelsForNotification($this);
        }

        $selectedChannels = $notifiable->getEnabledChannelsForNotification($notificationName);
        if ($selectedChannels === null) {
            return $manager->defaultChannelsForNotification($this);
        }

        return array_values(array_intersect(
            $manager->availableChannelsForNotification($this),
            $selectedChannels
        ));
    }
}
