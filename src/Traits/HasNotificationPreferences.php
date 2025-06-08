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
            get: function (string $value) use ($notificationConfigurator) {
                if (empty($notifications) || empty($channels))
                {
                    return [];
                }
                $preferences = $value ? (json_decode($value, true) ?: []) : [];
                $result = $notificationConfigurator->notificationPreferencesObject();
                foreach ($preferences as $event => $preferedChannels)
                {
                    if (!isset($result[$event]))
                    {
                        continue;
                    }
                    foreach ($preferedChannels as $channel)
                    {
                        if (isset($result[$event][$channel]))
                        {
                            $result[$event][$channel] = true;
                        }
                    }
                }
                return $result;
            },
            set: function (array $value) use ($notificationConfigurator) {
                [
                    'channels' => $channels,
                    'notifications' => $notifications
                ] = $notificationConfigurator->all();
                if (empty($notifications) || empty($channels))
                {
                    return [];
                }
                $filtered = [];
                foreach ($value as $event => $preferedChannels)
                {
                    if (!array_key_exists($event, $notifications))
                    {
                        continue;
                    }
                    $filtered[$event] = array_intersect_key(
                        $preferedChannels,
                        $channels
                    );
                }
                return json_encode($filtered);
            },
        )->withoutObjectCaching();
    }
}