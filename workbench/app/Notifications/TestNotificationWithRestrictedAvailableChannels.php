<?php

namespace Workbench\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Contracts\HasChannelSettings as HasChannelSettingsContract;
use KejKej\NotificationPreferences\Traits\HasChannelSettings;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class TestNotificationWithRestrictedAvailableChannels extends Notification implements HasChannelSettingsContract
{
    use Queueable, RoutesNotificationsViaPreferences, HasChannelSettings;

    // Only 'mail' and 'slack' are available for this notification
    protected array $availableChannels = ['mail', 'slack'];
    // Default is 'mail' if no user preference
    protected array $defaultChannels = ['mail'];

    public function __construct()
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->line('This is a test notification with restricted available channels (mail).');
    }

    public function toArray($notifiable) // For database channel
    {
        return [
            'message' => 'This is a test notification with restricted available channels (database).'
        ];
    }

    public function toSlack($notifiable) // Mocked to return an array
    {
        return [
            'content' => 'This is a test notification with restricted available channels (slack).'
        ];
    }
}
