# Upgrading to v3

Version 3 is a breaking API release.

Update the configuration from a class map plus global channels to structured notification definitions:

~~~php
'notifications' => [
    'post-commented' => [
        'class' => App\Notifications\PostCommented::class,
        'channels' => ['mail', 'database'],
        'default_channels' => ['mail'],
    ],
],
~~~

Remove HasChannelSettings from notifications. Channel availability and defaults now live in the matching config definition.

notification_preferences remains a JSON object containing explicit channel-list overrides, so existing valid stored data does not need a migration. The attribute is now symmetric: it reads and writes that override shape. Replace matrix DTO calls with:

~~~php
$options = $user->getNotificationPreferences();
$selected = $user->getNotificationPreference('post-commented');
~~~

The old matrix DTOs, configurator contracts, and getEnabledChannelsForNotification() method have been removed.
