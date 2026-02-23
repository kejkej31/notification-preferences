<?php

namespace KejKej\NotificationPreferences\Services;

use KejKej\NotificationPreferences\Contracts\NotificationConfigurator as NotificationConfiguratorContract;
use KejKej\NotificationPreferences\DTO\NotificationPreferenceRow;
use KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix;

class NotificationConfigurator implements NotificationConfiguratorContract
{
    public function notificationPreferencesObject(): NotificationPreferencesMatrix
    {
        $rows = [];
        $channels = $this->channels();

        foreach (array_keys($this->notifications()) as $notificationName) {
            $rows[$notificationName] = new NotificationPreferenceRow(
                notification: $notificationName,
                channels: array_fill_keys($channels, null),
            );
        }

        return new NotificationPreferencesMatrix($rows);
    }

    /**
     * Find a notification by its class name.
     *
     * @param string $name
     * @return string|null
     */
    public function findNotificationByClass(string $name): ?string
    {
        $notifications = $this->notifications();
        $key = array_search($name, $notifications, true);

        return $key !== false ? $key : null;
    }

    /**
     * Get the available notification types.
     *
     * @return array
     */
    public function notifications(): array
    {
        return config('notification-preferences.notifications', []);
    }

    /**
     * Get notification channels that should be used by default.
     *
     * @return array
     */
    public function defaultChannels(): array
    {
        return $this->sanitizeChannels(config('notification-preferences.default_channels', []));
    }

    /**
     * Get the available notification channels.
     *
     * @return array
     */
    public function channels(): array
    {
        return $this->sanitizeChannels(config('notification-preferences.channels', []));
    }

    public function availableChannelsForNotification(object $notification): array
    {
        $globalChannels = $this->channels();

        if ($this->supportsChannelSettings($notification)) {
            return array_values(array_intersect(
                $globalChannels,
                $this->sanitizeChannels($notification->getAvailableChannels())
            ));
        }

        return $globalChannels;
    }

    public function defaultChannelsForNotification(object $notification): array
    {
        $availableChannels = $this->availableChannelsForNotification($notification);

        if ($this->supportsChannelSettings($notification)) {
            return array_values(array_intersect(
                $availableChannels,
                $this->sanitizeChannels($notification->getDefaultChannels())
            ));
        }

        return array_values(array_intersect($availableChannels, $this->defaultChannels()));
    }

    public function normalizeStoredPreferences(mixed $value): array
    {
        $decodedPreferences = $this->decodePreferences($value);
        $normalized = [];
        $channelsLookup = array_flip($this->channels());

        foreach (array_keys($this->notifications()) as $notificationName) {
            if (!array_key_exists($notificationName, $decodedPreferences)) {
                continue;
            }

            $preferredChannels = $decodedPreferences[$notificationName];
            if (!is_array($preferredChannels)) {
                continue;
            }

            $filteredChannels = [];
            foreach ($preferredChannels as $channel) {
                if (!is_string($channel) || !array_key_exists($channel, $channelsLookup)) {
                    continue;
                }

                $filteredChannels[$channel] = $channel;
            }

            $normalized[$notificationName] = array_values($filteredChannels);
        }

        return $normalized;
    }

    public function preferenceMatrix(mixed $value): NotificationPreferencesMatrix
    {
        $normalized = $this->normalizeStoredPreferences($value);
        $matrix = $this->notificationPreferencesObject()->toPreferenceMap();

        foreach ($matrix as $notificationName => $channelMatrix) {
            if (!array_key_exists($notificationName, $normalized)) {
                continue;
            }

            $selectedLookup = array_flip($normalized[$notificationName]);
            foreach (array_keys($channelMatrix) as $channel) {
                $matrix[$notificationName][$channel] = array_key_exists($channel, $selectedLookup);
            }
        }

        $rows = [];
        foreach ($matrix as $notificationName => $channels) {
            $rows[$notificationName] = new NotificationPreferenceRow(
                notification: $notificationName,
                channels: $channels,
            );
        }

        return new NotificationPreferencesMatrix($rows);
    }

    public function selectedChannelsForNotification(mixed $value, string $notificationName): ?array
    {
        $decodedPreferences = $this->decodePreferences($value);

        if (!array_key_exists($notificationName, $decodedPreferences)) {
            return null;
        }

        $normalized = $this->normalizeStoredPreferences($decodedPreferences);

        return $normalized[$notificationName] ?? [];
    }

    /**
     * Get all notification preferences configuration.
     *
     * @return array
     */
    public function all(): array
    {
        return [
            'channels' => $this->channels(),
            'default_channels' => $this->defaultChannels(),
            'notifications' => $this->notifications(),
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    protected function decodePreferences(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * @param mixed $channels
     * @return array<int, string>
     */
    protected function sanitizeChannels(mixed $channels): array
    {
        if (!is_array($channels)) {
            return [];
        }

        $sanitized = [];
        foreach ($channels as $channel) {
            if (!is_string($channel) || $channel === '') {
                continue;
            }

            $sanitized[$channel] = $channel;
        }

        return array_values($sanitized);
    }

    protected function supportsChannelSettings(object $notification): bool
    {
        return method_exists($notification, 'getAvailableChannels')
            && method_exists($notification, 'getDefaultChannels');
    }
}
