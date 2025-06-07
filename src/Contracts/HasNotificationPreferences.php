<?php

namespace KejKej\NotificationPreferences\Contracts;

interface HasNotificationPreferences
{
    public function notificationPreferences(): array;
}