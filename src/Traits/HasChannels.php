<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\NotificationConfiguratorContract;

trait HasChannels
{
    public function getAvailableChannels(): array
    {
        if (property_exists($this, 'availableChannels')) {
            return $this->availableChannels;
        }
        return app(NotificationConfiguratorContract::class)->channels();
    }

    public function getDefaultChannels(): array
    {
        if (property_exists($this, 'defaultChannels')) {
            return $this->defaultChannels;
        }
        return app(NotificationConfiguratorContract::class)->channels();
    }
}
