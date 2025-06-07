# Laravel Notification Preferences

This package provides a flexible way to manage user notification preferences in a Laravel application.

## Features

- Allows users to specify their preferred notification channels (e.g., mail, database) for different types of notifications.
- Developers can easily register new notifiable events.
- Automatically routes notifications based on user preferences.
- Provides a configuration file for defining available notifications and channels.
- Includes database migrations to store user notification preferences.
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
    Ensure your notification classes use the `KejKej\\NotificationPreferences\\Traits\\RoutesNotificationsViaPreferences` trait. This trait will handle routing the notification based on the user's preferences.
    ```php
    // app/Notifications/PostCommented.php
    namespace App\\Notifications;

    use Illuminate\\Bus\\Queueable;
    use Illuminate\\Notifications\\Notification;
    use KejKej\\NotificationPreferences\\Traits\\RoutesNotificationsViaPreferences; // Add this

    class PostCommented extends Notification
    {
        use Queueable, RoutesNotificationsViaPreferences; // Add RoutesNotificationsViaPreferences here

        // ... rest of your notification class

        /**
         * Get the notification's delivery channels.
         *
         * @param  mixed  $notifiable
         * @return array
         */
        public function via($notifiable)
        {
            // This method might be overridden or influenced by RoutesNotificationsViaPreferences
            // Define default channels if no preference is set, or let the trait handle it.
            return $this->getChannels($notifiable, ['mail', 'database']);
        }

        // ... other methods like toMail, toArray, etc.
    }
    ```
    *Note: The `RoutesNotificationsViaPreferences` trait will likely dynamically determine the channels based on user settings. You might need to adjust the `via` method or rely on the trait's default behavior.*

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
        $user->updateNotificationPreferences($request->preferences);

        return back()->with('success', 'Notification preferences updated!');
    }

    public function getNotificationPreferences(User $user)
    {
        $preferences = $user->getNotificationPreferences();
        // $preferences will be an array of the user's current settings
        // e.g., ['PostCommented' => ['mail', 'database']]

        $availableNotifications = config('notification-preferences.notifications');
        $availableChannels = config('notification-preferences.channels');

        // Pass this data to a view to render the preferences form
        return view('profile.notification-settings', compact('user', 'preferences', 'availableNotifications', 'availableChannels'));
    }
    ```

4.  **Sending Notifications:**
    When you send a notification as usual, the package will automatically check the user's preferences and deliver the notification only through the selected channels.

    ```php
    $user->notify(new PostCommented($post));
    ```

This provides a more comprehensive starting point for your `README.md`. You'll still want to review and refine it, especially the "Usage" section, to provide clear and accurate examples specific to how you intend the package to be used. For instance, the exact methods for getting/setting preferences (`updateNotificationPreferences`, `getNotificationPreferences`) are inferred and might need to be adjusted based on the actual implementation within the `HasNotificationPreferences` trait.
