<?php

namespace KejKej\NotificationPreferences\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class NotificationPreferenceRow implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, bool|null>  $channels
     */
    public function __construct(
        public readonly string $notification,
        public readonly array $channels,
    ) {}

    /**
     * @return array<int, string>
     */
    public function enabledChannels(): array
    {
        return array_keys(array_filter($this->channels, static fn (?bool $enabled) => $enabled === true));
    }

    /**
     * @return array<int, string>
     */
    public function disabledChannels(): array
    {
        return array_keys(array_filter($this->channels, static fn (?bool $enabled) => $enabled === false));
    }

    /**
     * @return array<int, string>
     */
    public function unsetChannels(): array
    {
        return array_keys(array_filter($this->channels, static fn (?bool $enabled) => $enabled === null));
    }

    /**
     * @return array{notification: string, channels: array<string, bool|null>}
     */
    public function toArray(): array
    {
        return [
            'notification' => $this->notification,
            'channels' => $this->channels,
        ];
    }

    /**
     * @return array{notification: string, channels: array<string, bool|null>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
