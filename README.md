# Laravel Notification Preferences

Small, config-driven notification preferences for Laravel notifiables.

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13

## Installation

```bash
composer require kejkej/notification-preferences
php artisan vendor:publish --provider="KejKej\\NotificationPreferences\\NotificationPreferencesServiceProvider" --tag="notification-preferences-config"
php artisan vendor:publish --provider="KejKej\\NotificationPreferences\\NotificationPreferencesServiceProvider" --tag="notification-preferences-migrations"
php artisan migrate
```

The migration is publish-only because applications may use a custom notifiable table. Edit the published migration when the JSON column does not belong on `users`.

## Configuration

Each notification has a stable key independent of its PHP class name:

```php
return [
    'notifications' => [
        'post-commented' => [
            'class' => App\Notifications\PostCommented::class,
            'channels' => ['mail', 'database'],
            'default_channels' => ['mail'],
        ],
    ],
];
```

The registry validates definitions and rejects duplicate classes, unknown defaults, and malformed channel lists.

## User model

Add the trait to any model containing a nullable JSON `notification_preferences` column:

```php
use KejKej\NotificationPreferences\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use HasNotificationPreferences;
}
```

The attribute is symmetric: it always reads and writes explicit overrides in this shape:

```php
$user->notification_preferences = [
    'post-commented' => ['mail'],
];
$user->save();
```

An omitted key uses its configured defaults. An explicit empty list disables every channel for that notification.

For settings forms, `getNotificationPreferences()` returns every configured notification with its available channels, defaults, nullable selected channels, and effective channels:

```php
$options = $user->getNotificationPreferences();
```

`getNotificationPreference('post-commented')` returns the explicit channel list or `null` when no override exists.

Invalid keys, channels, and payload shapes are rejected when writing. Obsolete keys and channels already persisted in the database are ignored when reading until the record is rewritten.

## Notification routing

Use the routing trait on notifications registered in the config:

```php
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class PostCommented extends Notification
{
    use RoutesNotificationsViaPreferences;
}
```

The trait returns the user’s explicit channels, or the configured defaults when no override exists. The selected list is always filtered by the channels declared for that notification. Notifications that should bypass preferences should not use the trait.

## Development

```bash
composer install
composer test
composer lint
composer analyse
composer quality
```

The CI matrix runs the package against Laravel 11/Testbench 9, Laravel 12/Testbench 10, and Laravel 13/Testbench 11.
