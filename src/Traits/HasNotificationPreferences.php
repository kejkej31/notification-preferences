<?php

namespace KejKej\NotificationPreferences\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use KejKej\NotificationPreferences\Contracts\NotificationConfiguratorContract;

trait HasNotificationPreferences
{
    /**
     * Get the notification preferences for the notifiable entity.
     */
    protected function notificationPreferences(): Attribute
    {
        [
            'channels' => $channels,
            'notifications' => $notifications
        ] = app(NotificationConfiguratorContract::class)->all();
        return Attribute::make(
            get: function (string $value) use ($channels, $notifications) {
                if (empty($notifications) || empty($channels)) {
                    return [];
                }
                $preferences = $value ? (json_decode($value, true) ?: []) : [];
                
                $result = [];
                foreach ($notifications as $event) {
                    $eventPreferences = $preferences[$event] ?? [];
                    $result[$event] = array_map(
                        fn($channel) => (bool) ($eventPreferences[$channel] ?? false),
                        array_flip($channels)
                    );
                }
                return $result;
            },
            set: function (array $value) use ($channels, $notifications) {
                if (empty($notifications) || empty($channels)) {
                    return [];
                }
                
                $filtered = [];
                foreach ($value as $event => $eventChannels) {
                    if (!in_array($event, $notifications)) {
                        continue;
                    }
                    
                    $filtered[$event] = array_intersect_key(
                        $eventChannels,
                        array_flip($channels)
                    );
                }
                
                return json_encode($filtered);
            },
        );
    }
}