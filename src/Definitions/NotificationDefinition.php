<?php

namespace KejKej\NotificationPreferences\Definitions;

final readonly class NotificationDefinition
{
    /**
     * @param  class-string  $class
     * @param  list<string>  $channels
     * @param  list<string>  $defaultChannels
     */
    public function __construct(
        public string $key,
        public string $class,
        public array $channels,
        public array $defaultChannels,
    ) {}
}
