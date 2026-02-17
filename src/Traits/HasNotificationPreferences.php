<?php

namespace KejKej\NotificationPreferences\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use KejKej\NotificationPreferences\Contracts\NotificationConfigurator;

trait HasNotificationPreferences
{
    public function getNotificationPreferences(): array
    {
        return $this->notification_preferences;
    }

    /**
     * Get the notification preferences for the notifiable entity.
     */
    public function notificationPreferences(): Attribute
    {
        $notificationConfigurator = app(NotificationConfigurator::class);

        return Attribute::make(
            get: function (?string $value) use ($notificationConfigurator) {
                $preferences = $value ? (json_decode($value, true) ?: []) : [];
                $configuredPreferences = $notificationConfigurator->notificationPreferencesObject();
                $result = \is_array($configuredPreferences) ? $configuredPreferences : [];

                foreach ($result as $event => $channels) {
                    if (!\is_array($channels) || !isset($preferences[$event]) || !is_array($preferences[$event])) {
                        continue;
                    }

                    $result[$event] = array_replace($channels, $preferences[$event]);
                }

                return $result;
            },
            set: function (array $value) use ($notificationConfigurator) {
                [
                    'channels' => $channels,
                    'notifications' => $notifications,
                ] = $notificationConfigurator->all();

                $filtered = [];
                foreach ($value as $event => $preferredChannels) {
                    if (!array_key_exists($event, $notifications)) {
                        continue;
                    }

                    $filtered[$event] = array_intersect_key(
                        $preferredChannels,
                        array_flip($channels)
                    );
                }

                return json_encode($filtered);
            },
        )->withoutObjectCaching();
    }
}