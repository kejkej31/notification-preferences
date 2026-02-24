<?php

namespace KejKej\NotificationPreferences\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use KejKej\NotificationPreferences\Contracts\NotificationConfigurator;
use KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix;

trait HasNotificationPreferences
{
    public function getNotificationPreferences(): NotificationPreferencesMatrix
    {
        return app(NotificationConfigurator::class)
            ->preferenceMatrix($this->getRawStoredNotificationPreferences());
    }

    /**
     * @return array<string, array<string, bool|null>>
     */
    public function getNotificationPreferencesMap(): array
    {
        return $this->getNotificationPreferences()->toPreferenceMap();
    }

    public function getEnabledChannelsForNotification(string $notificationName): ?array
    {
        return app(NotificationConfigurator::class)
            ->selectedChannelsForNotification($this->getRawStoredNotificationPreferences(), $notificationName);
    }

    /**
     * Get the notification preferences for the notifiable entity.
     */
    public function notificationPreferences(): Attribute
    {
        $notificationConfigurator = app(NotificationConfigurator::class);

        return Attribute::make(
            get: function (?string $value) use ($notificationConfigurator) {
                return $notificationConfigurator->preferenceMatrix($value)->toPreferenceMap();
            },
            set: function (mixed $value) use ($notificationConfigurator) {
                return json_encode($notificationConfigurator->normalizeStoredPreferences($value));
            },
        )->withoutObjectCaching();
    }

    protected function getRawStoredNotificationPreferences(): mixed
    {
        if (method_exists($this, 'getRawOriginal')) {
            return $this->getRawOriginal('notification_preferences');
        }

        return $this->attributes['notification_preferences'] ?? null;
    }
}
