<?php

namespace KejKej\NotificationPreferences\DTO;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class NotificationPreferencesMatrix implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, NotificationPreferenceRow>  $rows
     */
    public function __construct(
        protected array $rows,
    ) {}

    /**
     * @return array<string, NotificationPreferenceRow>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    public function row(string $notification): ?NotificationPreferenceRow
    {
        return $this->rows[$notification] ?? null;
    }

    /**
     * @return array<int, string>|null
     */
    public function selectedChannelsFor(string $notification): ?array
    {
        $row = $this->row($notification);

        return $row?->enabledChannels();
    }

    /**
     * @return array<string, array<string, bool|null>>
     */
    public function toPreferenceMap(): array
    {
        $matrix = [];

        foreach ($this->rows as $notification => $row) {
            $matrix[$notification] = $row->channels;
        }

        return $matrix;
    }

    /**
     * @return array<string, array{notification: string, channels: array<string, bool|null>}>
     */
    public function toArray(): array
    {
        $rows = [];

        foreach ($this->rows as $notification => $row) {
            $rows[$notification] = $row->toArray();
        }

        return $rows;
    }

    /**
     * @return array<string, array{notification: string, channels: array<string, bool|null>}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
