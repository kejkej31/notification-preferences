<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Services\NotificationRegistry;

trait RoutesNotificationsViaPreferences
{
    /**
     * Get the notification channels.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        $registry = app(NotificationRegistry::class);
        $notificationName = $registry->keyForClass(static::class);

        if ($notificationName === null) {
            throw new \LogicException(
                sprintf('Notification [%s] uses RoutesNotificationsViaPreferences but is not registered.', static::class),
            );
        }

        $definition = $registry->definition($notificationName);
        if ($definition === null) {
            throw new \LogicException("Notification preference definition [{$notificationName}] could not be loaded.");
        }

        if (! is_object($notifiable) || ! method_exists($notifiable, 'getNotificationPreference')) {
            return $definition->defaultChannels;
        }

        $selectedChannels = $notifiable->getNotificationPreference($notificationName);

        return array_values(array_intersect(
            $definition->channels,
            $selectedChannels ?? $definition->defaultChannels,
        ));
    }
}
