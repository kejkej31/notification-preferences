# Laravel Notification Preferences

Simple, config-driven notification preferences for Laravel users.

## Features

- Save per-notification channel preferences (e.g. `mail`, `database`).
- Automatically route notifications through selected channels.
- Configure notification types and global channels in one place.
- Optional per-notification channel restrictions/defaults.

## Installation

1. Install with Composer:
    ```bash
    composer require kejkej/laravel-notification-preferences
    ```
2. Publish config:
    ```bash
    php artisan vendor:publish --provider="KejKej\\NotificationPreferences\\NotificationPreferencesServiceProvider" --tag="notification-preferences-config"
    ```
3. Publish migration:
    ```bash
    php artisan vendor:publish --provider="KejKej\\NotificationPreferences\\NotificationPreferencesServiceProvider" --tag="notification-preferences-migrations"
    ```
4. Run migrations:
    ```bash
    php artisan migrate
    ```

## Configuration

Edit `config/notification-preferences.php`:

```php
return [
    'notifications' => [
        'PostCommented' => App\Notifications\PostCommented::class,
        'NewFollower' => App\Notifications\NewFollower::class,
    ],

    'channels' => [
        'mail',
        'database',
    ],

    'default_channels' => [
        'mail',
    ],
];
```

## Usage

### 1) Add trait to user model

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use KejKej\NotificationPreferences\Traits\HasNotificationPreferences;

class User extends Authenticatable
{
    use Notifiable, HasNotificationPreferences;
}
```

### 2) Add routing trait to notifications

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use KejKej\NotificationPreferences\Traits\RoutesNotificationsViaPreferences;

class PostCommented extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences;
}
```

### 3) Optional per-notification channels/defaults

```php
use KejKej\NotificationPreferences\Traits\HasChannelSettings;

class PostCommented extends Notification
{
    use Queueable, RoutesNotificationsViaPreferences, HasChannelSettings;

    protected array $availableChannels = ['mail', 'database'];
    protected array $defaultChannels = ['mail'];
}
```

### 4) Save preferences

```php
$user->notification_preferences = [
    'PostCommented' => ['mail', 'database'],
    'NewFollower' => ['mail'],
];

$user->save();
```

Stored format is a channel list per notification key, for example:

```json
{"PostCommented":["mail"]}
```

Unknown notification keys/channels are dropped on write.

### 5) Read preferences matrix

```php
$matrix = $user->getNotificationPreferences();

// DTO:
// KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix

$map = $matrix->toPreferenceMap();
```

Matrix values:
- `true`: user enabled this channel
- `false`: user has preferences for this notification, but did not select this channel
- `null`: user has no preferences for this notification

### 6) Send notifications normally

```php
$user->notify(new PostCommented($post));
```

## Channel resolution rules

- No user preference for a notification:
  - use notification-level defaults (when `HasChannelSettings` is used), otherwise global `default_channels`
- User preference exists:
  - use selected channels filtered by notification availability
  - if all selected channels are invalid/unavailable, notification is not sent
