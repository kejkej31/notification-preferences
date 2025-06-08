<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\HasChannelSettings;
use KejKej\NotificationPreferences\Contracts\HasNotificationPreferences;
use KejKej\NotificationPreferences\Contracts\NotificationConfigurator;

trait RoutesNotificationsViaPreferences
{
    /**
     * Get the notification channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via(mixed $notifiable): array
    {
            $manager = app(NotificationConfigurator::class);
        if (
            method_exists($notifiable, 'notificationPreferences') && 
            $notifiable instanceof HasNotificationPreferences
        ) {
            $notificationName = $manager->findNotificationByClass($this::class);
            
            $preferences = $notifiable->getNotificationPreferences();

            if (isset($preferences[$notificationName])) {
                $activeChannels = $preferences[$notificationName];

                if($this instanceof HasChannelSettings) {
                    $availableChannels = $this->getAvailableChannels();
                    $activeChannels = array_intersect(
                        $activeChannels, 
                        $availableChannels
                    );
                }

                return $activeChannels;
            }
        }

        if($this instanceof HasChannelSettings) {
            return $this->getDefaultChannels();
        }

        return $manager->defaultChannels();
    }
}
