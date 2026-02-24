# Laravel Notification Preferences

Simple, config-driven notification preferences for Laravel users.

## Requirements

- PHP 8.1+
- Laravel support (declared): `illuminate/support ^9|^10|^11|^12`

## Features

- Save per-notification channel preferences (e.g. `mail`, `database`).
- Automatically route notifications through selected channels.
- Configure notification types and global channels in one place.
- Optional per-notification channel restrictions/defaults.
- Defensive normalization of persisted preferences.

## Installation

1. Install with Composer:
   ```bash
   composer require kejkej/notification-preferences
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

## Data model

The package stores user preferences in a JSON column named `notification_preferences` on the users table.

Stored format is a channel list per notification key, for example:

```json
{"PostCommented":["mail"]}
```

Normalization behavior:

- Unknown notification keys are dropped on write.
- Unknown channels are dropped on write.
- Invalid payload shapes are tolerated on read and normalized into an empty/partial matrix.

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

### 5) Read preferences matrix

```php
$matrix = $user->getNotificationPreferences();
$map = $matrix->toPreferenceMap();
```

Matrix values:

- `true`: user enabled this channel.
- `false`: user has preferences for this notification, but did not select this channel.
- `null`: user has no preferences for this notification.

### 6) Send notifications normally

```php
$user->notify(new PostCommented($post));
```

## Channel resolution rules

- No user preference for a notification:
  - use notification-level defaults (when `HasChannelSettings` is used), otherwise global `default_channels`.
- User preference exists:
  - use selected channels filtered by notification availability.
  - if all selected channels are invalid/unavailable, notification is not sent.

## Local development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run style checks:

```bash
composer lint
```

Run static analysis:

```bash
composer analyse
```

Clear local quality caches:

```bash
composer clean-cache
```

Run all quality checks:

```bash
composer quality
```

Create and push the next release tag from `HEAD`:

```bash
composer patch # 2.0.1 -> 2.0.2
composer minor # 2.0.1 -> 2.1.0
composer major # 2.0.1 -> 3.0.0
```

These commands fetch tags, calculate the next semantic version from the latest tag,
create the new tag on the latest commit, and push that tag to the tracked remote.

Enable repository git hooks (required once per clone):

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit
```

## CI quality gate rollout

- Phase 1 (current): tests are required, while lint/static analysis run in soft-gate mode in CI.
- Phase 2: switch lint/static analysis steps to required once formatting debt is cleaned up.
