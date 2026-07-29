<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class TestNotificationWithRestrictedAvailableChannels extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences;

    public function __construct()
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('This is a test notification with restricted available channels (mail).');
    }

    public function toArray($notifiable) // For database channel
    {
        return [
            'message' => 'This is a test notification with restricted available channels (database).',
        ];
    }

    public function toSlack($notifiable) // Mocked to return an array
    {
        return [
            'content' => 'This is a test notification with restricted available channels (slack).',
        ];
    }
}
