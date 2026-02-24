<?php

namespace KejKej\NotificationPreferences\Contracts;

use KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix;

interface NotificationConfigurator
{
    /**
     * Get full notification preferences object.
     * This object contains all available notifications and channels,
     * with values set to false.
     */
    public function notificationPreferencesObject(): NotificationPreferencesMatrix;

    /**
     * Find a notification by its class name.
     */
    public function findNotificationByClass(string $name): ?string;

    /**
     * Get the available notification channels.
     */
    public function channels(): array;

    /**
     * Get the available notification types.
     */
    public function notifications(): array;

    /**
     * Get notification channels that should be used by default
     */
    public function defaultChannels(): array;

    /**
     * Get available channels for a concrete notification instance.
     *
     * @return array<int, string>
     */
    public function availableChannelsForNotification(object $notification): array;

    /**
     * Get default channels for a concrete notification instance.
     *
     * @return array<int, string>
     */
    public function defaultChannelsForNotification(object $notification): array;

    /**
     * Normalize user provided preferences to a storable shape.
     *
     * @return array<string, array<int, string>>
     */
    public function normalizeStoredPreferences(mixed $value): array;

    /**
     * Build a full notification/channel matrix for UI consumption.
     */
    public function preferenceMatrix(mixed $value): NotificationPreferencesMatrix;

    /**
     * Get user-selected channels for a specific notification key.
     *
     * @return array<int, string>|null
     */
    public function selectedChannelsForNotification(mixed $value, string $notificationName): ?array;

    /**
     * Get all notification preferences configuration.
     */
    public function all(): array;
}
