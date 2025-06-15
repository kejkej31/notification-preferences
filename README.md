# Laravel Notification Preferences

This package provides a flexible way to manage user notification preferences in a Laravel application.

## Features

- Allows users to specify their preferred notification channels (e.g., mail, database) for different types of notifications.
- Automatically routes notifications based on user preferences.
- Developer can define available notifications and channels, both globally and per notification.
- Uses traits to easily integrate notification preference management into existing User models and Notification classes.

## Installation

1.  You can install the package via composer:
    ```bash
    composer require kejkej/laravel-notification-preferences
    ```
2.  Publish the configuration file:
    ```bash
    php artisan vendor:publish --provider="KejKej\\NotificationPreferences\\NotificationPreferencesServiceProvider" --tag="config"
    ```
3.  Run the database migrations:
    ```bash
    php artisan migrate
    ```

## Configuration

1.  After publishing the configuration file, you can find it at `config/notification-preferences.php`.
2.  **Register Notifications:**
    In the `notifications` array, define the unique key for each notification type and map it to its corresponding notification class.
    ```php
    // config/notification-preferences.php
    'notifications' => [
        'PostCommented' => App\\Notifications\\PostCommented::class,
        'NewFollower' => App\\Notifications\\NewFollower::class,
        // ... other notifications
    ],
    ```
3.  **Define Channels:**
    In the `channels` array, list all the notification channels you want to make available for user preferences.
    ```php
    // config/notification-preferences.php
    'channels' => [
        'mail',
        'database',
        // 'slack', // Example of another channel
    ],
    ```

## Usage

1.  **Prepare your User model:**
    Add the `KejKej\\NotificationPreferences\\Traits\\HasNotificationPreferences` trait to your `User` model (or any other notifiable model).
    ```php
    // app/Models/User.php
    use Illuminate\\Foundation\\Auth\\User as Authenticatable;
    use Illuminate\\Notifications\\Notifiable;
    use KejKej\\NotificationPreferences\\Traits\\HasNotificationPreferences; // Add this

    class User extends Authenticatable
    {
        use Notifiable, HasNotificationPreferences; // Add HasNotificationPreferences here

        // ... rest of your User model
    }
    ```

2.  **Prepare your Notification classes:**

    To integrate with the preference system, your notification classes need to:
    *   Use the `KejKej\\NotificationPreferences\\Traits\\RoutesNotificationsViaPreferences` trait. This trait provides the `via()` method that dynamically determines channels based on user preferences.
    *   Optionally, implement the `KejKej\\NotificationPreferences\\Contracts\\HasChannelSettings` interface and use the `KejKej\\NotificationPreferences\\Traits\\HasChannelSettings` trait if you want to define specific available channels for a notification or set its default channels.

    Here's how you can set it up:

    ```php
    // app/Notifications/PostCommented.php
    namespace App\\Notifications;

    use Illuminate\\Bus\\Queueable;
    use Illuminate\\Notifications\\Notification;
    use KejKej\\NotificationPreferences\\Contracts\\HasChannelSettings as HasChannelSettingsContract; // Add this
    use KejKej\\NotificationPreferences\\Traits\\HasChannelSettings; // Add this
    use KejKej\\NotificationPreferences\\Traits\\RoutesNotificationsViaPreferences; // Add this

    class PostCommented extends Notification implements HasChannelSettingsContract // Implement the contract
    {
        use Queueable, RoutesNotificationsViaPreferences, HasChannelSettings; // Add the traits

        /**
         * OPTIONAL
         * Define the channels this notification can be sent on.
         * If not defined, it will fall back to the global channels 
         * defined in `config/notification-preferences.php`.
         */
        // protected array $availableChannels = ['mail', 'database'];

        /**
         * OPTIONAL
         * Define the channels that should be enabled by default for this notification
         * if the user has not set any specific preferences for it.
         * If not defined, it will fall back to the global default channels 
         * defined in `config/notification-preferences.php`.
         */
        // protected array $defaultChannels = ['mail'];

        // ... rest of your notification class (e.g., __construct, toMail, toArray)

        // IMPORTANT: The RoutesNotificationsViaPreferences trait defines via() method
        // If you define your own via() method, it will override the trait's logic.
    }
    ```

    **Explanation:**

    *   **`RoutesNotificationsViaPreferences`**: This is essential. It replaces the standard `via()` method logic to check user preferences.
    *   **`HasChannelSettingsContract` & `HasChannelSettings` Trait (Optional but Recommended for fine-grained control):**
        *   By implementing `HasChannelSettingsContract` and using the `HasChannelSettings` trait, your notification can specify its own set of available channels and default channels.
        *   **`$availableChannels` property**: If you define this protected property in your notification (e.g., `protected array $availableChannels = ['mail', 'database'];`), only these channels will be considered for this specific notification, even if more are globally available. If a user has a preference for a channel not in this list for *this* notification, it won't be used.
        *   **`$defaultChannels` property**: If you define this protected property (e.g., `protected array $defaultChannels = ['mail'];`), these channels will be used for the notification if the user hasn't set any preferences for it *and* this notification uses the `HasChannelSettings` trait.
        *   If these properties are not set in your notification class, the `HasChannelSettings` trait will fall back to the global `channels` and `defaultChannels` (derived from global `channels`) defined in your `config/notification-preferences.php` file.

    *Note: If you don't use the `HasChannelSettings` interface and trait on a notification, the `RoutesNotificationsViaPreferences` trait will use the globally configured default channels from `config/notification-preferences.php` when no user preference is found.*

3.  **Managing Preferences (Example):**
    You will need to build an interface for users to manage their preferences. Here's a conceptual example of how you might update preferences in your controller:

    ```php
    // Example in a controller
    use Illuminate\\Http\\Request;
    use App\\Models\\User;

    public function updateNotificationPreferences(Request $request, User $user)
    {
        // Assuming $request->preferences is an array like:
        // [
        //     'PostCommented' => ['mail', 'database'],
        //     'NewFollower' => ['mail'],
        // ]
        $user->notification_preferences = $request->preferences;

        return back()->with('success', 'Notification preferences updated!');
    }

    public function getNotificationPreferences(User $user)
    {
        $preferences = $user->getNotificationPreferences(); // Method from HasNotificationPreferences trait
        // $preferences contains all notification types defined in your config.
        // For each notification type:
        // - If the user has set preferences for this type:
        //   - Channels explicitly enabled by the user will be `true`.
        //   - Channels not explicitly enabled by the user (but available for this type) will be `false`.
        // - If the user has NOT set any preferences for this notification type:
        //   - All channels for this type will be `null`.
        //
        // Example:
        // Assuming config has "PostCreated" and "CommentReplied", each with "mail", "database", "sms" channels.
        // User's saved data in database for `notification_preferences` column: {"PostCreated":["mail"]}
        //
        // Returned array from $user->getNotificationPreferences():
        // [
        //    "PostCreated" => [
        //      "mail" => true,     // User enabled this
        //      "database" => false,  // User did not enable this, but has settings for PostCreated
        //      "sms" => false      // User did not enable this, but has settings for PostCreated
        //    ],
        //    "CommentReplied" => [ // User has no settings for CommentReplied
        //      "mail" => null,
        //      "database" => null,
        //      "sms" => null
        //    ]
        // ]

        // Pass this data to a view to render the preferences form
        return view('profile.notification-preferences', compact('preferences'));
    }
    ```

4.  **Sending Notifications:**
    When you send a notification as usual, the package will automatically check the user's preferences and deliver the notification only through the selected channels.

    ```php
    $user->notify(new PostCommented($post));
    ```
