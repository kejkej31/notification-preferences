<?php

namespace KejKej\NotificationPreferences\Contracts;

interface HasNotificationPreferences
{
    public function getNotificationPreferences(): array;
}