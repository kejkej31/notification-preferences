<?php

namespace KejKej\NotificationPreferences\Traits;

use KejKej\NotificationPreferences\Contracts\HasNotificationPreferences;

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
        if (
            method_exists($notifiable, 'notificationPreferences') && 
            $notifiable instanceof HasNotificationPreferences
        ) {
            $notificationName = $this::class;
            $preferences = $notifiable->notificationPreferences();

            if (isset($preferences[$notificationName])) {
                $activeChannels = [];
                foreach ($preferences[$notificationName] as $channel => $isEnabled) {
                    if ($isEnabled) {
                        $activeChannels[] = $channel;
                    }
                }
                if (!empty($activeChannels)) {
                    return $activeChannels;
                }
            }
        }

        if (method_exists(parent::class, 'via')) {
            return parent::via($notifiable);
        }

        return [];
    }
}
