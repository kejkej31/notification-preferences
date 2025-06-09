<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Contracts\HasChannelSettings as HasChannelSettingsContract;
use KejKej\NotificationPreferences\Traits\HasChannelSettings;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class TestNotificationWithDefaults extends Notification implements HasChannelSettingsContract
{
    use Queueable, RoutesNotificationsViaPreferences, HasChannelSettings;

    protected array $availableChannels = ['mail', 'database', 'slack'];
    protected array $defaultChannels = ['mail', 'database'];

    public function __construct()
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->line('This is a test notification with defaults (mail).');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'This is a test notification with defaults (database).'
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
