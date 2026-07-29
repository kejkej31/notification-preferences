<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class TestNotificationWithoutDefaults extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences;

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('This is a test notification without defaults (mail).');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'This is a test notification without defaults (database).',
        ];
    }
}
