<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\HasChannels;
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

                if($this instanceof HasChannels) {
                    $availableChannels = $this->getAvailableChannels();
                    $activeChannels = array_intersect(
                        $activeChannels, 
                        $availableChannels
                    );
                }

                return $activeChannels;
            }
        }

        if($this instanceof HasChannels) {
            return $this->getDefaultChannels();
        }

        return $manager->defaultChannels();
    }
}
