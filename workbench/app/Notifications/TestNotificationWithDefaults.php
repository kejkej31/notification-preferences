<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class TestNotificationWithDefaults extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences;

    public function __construct()
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('This is a test notification with defaults (mail).');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'This is a test notification with defaults (database).',
        ];
    }

    // Example for a potential toSlack method, if you were to use it
    /*
    public function toSlack($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\SlackMessage)
                    ->content('This is a test notification with defaults (slack).');
    }
    */
}
