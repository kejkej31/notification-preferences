<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;
// No HasChannelSettingsContract or HasChannelSettings trait here, so it will use global defaults

class TestNotificationWithoutDefaults extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences;

    public function __construct()
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->line('This is a test notification without defaults (mail).');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'This is a test notification without defaults (database).'
        ];
    }

    // Example for a potential toSlack method
    /*
    public function toSlack($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\SlackMessage)
                    ->content('This is a test notification without defaults (slack).');
    }
    */
}
