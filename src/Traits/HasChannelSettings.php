<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\NotificationConfigurator;

trait HasChannelSettings
{
    public function getAvailableChannels(): array
    {
        if (property_exists($this, 'availableChannels')) {
            return $this->availableChannels;
        }
        return app(NotificationConfigurator::class)->channels();
    }

    public function getDefaultChannels(): array
    {
        if (property_exists($this, 'defaultChannels')) {
            return $this->defaultChannels;
        }
        return app(NotificationConfigurator::class)->defaultChannels();
    }
}
