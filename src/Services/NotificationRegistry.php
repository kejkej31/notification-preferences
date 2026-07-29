<?php

namespace KejKej\NotificationPreferences\Services;

use KejKej\NotificationPreferences\Definitions\NotificationDefinition;

final class NotificationRegistry
{
    /**
     * @return array<string, NotificationDefinition>
     */
    public function definitions(): array
    {
        $configured = config('notification-preferences.notifications', []);

        if (! is_array($configured)) {
            throw new \InvalidArgumentException('notification-preferences.notifications must be an array.');
        }

        $definitions = [];

        foreach ($configured as $key => $definition) {
            if (! is_string($key) || $key === '') {
                throw new \InvalidArgumentException('Notification preference keys must be non-empty strings.');
            }

            if (! is_array($definition)) {
                throw new \InvalidArgumentException("Notification definition [{$key}] must be an array.");
            }

            $class = $definition['class'] ?? null;
            if (! is_string($class) || $class === '' || ! class_exists($class)) {
                throw new \InvalidArgumentException("Notification definition [{$key}] must contain an existing class.");
            }

            $channels = $this->channels($definition['channels'] ?? null, "notification [{$key}]");
            $defaultChannels = $this->channels(
                $definition['default_channels'] ?? [],
                "default channels for notification [{$key}]",
                allowEmpty: true,
            );

            if (array_diff($defaultChannels, $channels) !== []) {
                throw new \InvalidArgumentException(
                    "Default channels for notification [{$key}] must be available on that notification.",
                );
            }

            $definitions[$key] = new NotificationDefinition(
                key: $key,
                class: $class,
                channels: $channels,
                defaultChannels: $defaultChannels,
            );
        }

        $classes = array_map(static fn (NotificationDefinition $definition): string => $definition->class, $definitions);
        if (count($classes) !== count(array_unique($classes))) {
            throw new \InvalidArgumentException('Each notification class may only have one preference key.');
        }

        return $definitions;
    }

    public function definition(string $key): ?NotificationDefinition
    {
        return $this->definitions()[$key] ?? null;
    }

    public function keyForClass(string $class): ?string
    {
        foreach ($this->definitions() as $definition) {
            if ($definition->class === $class) {
                return $definition->key;
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function normalizeStoredPreferences(mixed $value, bool $strict): array
    {
        if (! is_array($value)) {
            throw new \InvalidArgumentException('Notification preferences must be an array.');
        }

        $definitions = $this->definitions();
        $normalized = [];

        foreach ($value as $key => $channels) {
            if (! is_string($key) || ! isset($definitions[$key])) {
                if ($strict) {
                    throw new \InvalidArgumentException("Unknown notification preference [{$key}].");
                }

                continue;
            }

            if (! is_array($channels)) {
                throw new \InvalidArgumentException("Channels for notification [{$key}] must be an array.");
            }

            $available = array_flip($definitions[$key]->channels);
            $selected = [];

            foreach ($channels as $channel) {
                if (! is_string($channel) || ! isset($available[$channel])) {
                    if ($strict) {
                        throw new \InvalidArgumentException(
                            "Unknown channel for notification [{$key}] in notification preferences.",
                        );
                    }

                    continue;
                }

                if (! in_array($channel, $selected, true)) {
                    $selected[] = $channel;
                }
            }

            $normalized[$key] = $selected;
        }

        return $normalized;
    }

    /**
     * @return array<string, array{channels: list<string>, default_channels: list<string>, selected_channels: list<string>|null, effective_channels: list<string>}>
     */
    public function preferenceOptions(mixed $value): array
    {
        $preferences = $this->normalizeStoredPreferences($value, strict: false);
        $options = [];

        foreach ($this->definitions() as $key => $definition) {
            $selected = array_key_exists($key, $preferences) ? $preferences[$key] : null;

            $options[$key] = [
                'channels' => $definition->channels,
                'default_channels' => $definition->defaultChannels,
                'selected_channels' => $selected,
                'effective_channels' => $selected ?? $definition->defaultChannels,
            ];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function channels(mixed $channels, string $context, bool $allowEmpty = false): array
    {
        if (! is_array($channels)) {
            throw new \InvalidArgumentException("Channels for {$context} must be an array.");
        }

        $normalized = [];
        foreach ($channels as $channel) {
            if (! is_string($channel) || $channel === '') {
                throw new \InvalidArgumentException("Channels for {$context} must contain non-empty strings.");
            }

            if (! in_array($channel, $normalized, true)) {
                $normalized[] = $channel;
            }
        }

        if (! $allowEmpty && $normalized === []) {
            throw new \InvalidArgumentException("Channels for {$context} may not be empty.");
        }

        return $normalized;
    }
}
