<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Casts\NotificationPreferencesCast;
use KejKej\NotificationPreferences\Services\NotificationRegistry;

trait HasNotificationPreferences
{
    public function initializeHasNotificationPreferences(): void
    {
        $this->mergeCasts([
            'notification_preferences' => NotificationPreferencesCast::class,
        ]);
    }

    /**
     * @return array<string, array{channels: list<string>, default_channels: list<string>, selected_channels: list<string>|null, effective_channels: list<string>}>
     */
    public function getNotificationPreferences(): array
    {
        return app(NotificationRegistry::class)->preferenceOptions($this->getAttribute('notification_preferences') ?? []);
    }

    /**
     * @return list<string>|null
     */
    public function getNotificationPreference(string $notificationName): ?array
    {
        $preferences = $this->getAttribute('notification_preferences') ?? [];

        return array_key_exists($notificationName, $preferences)
            ? $preferences[$notificationName]
            : null;
    }
}
