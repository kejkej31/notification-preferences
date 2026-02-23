<?php

namespace KejKej\NotificationPreferences\Tests\Feature;

use Workbench\App\Models\User;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Notification;
use Orchestra\Testbench\Attributes\WithMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workbench\App\Notifications\TestNotificationWithDefaults;
use Workbench\App\Notifications\TestNotificationWithoutDefaults;
use Workbench\App\Notifications\TestNotificationWithRestrictedAvailableChannels;

#[WithMigration]
class NotificationPreferencesTest extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app)
    {
        return [
            \KejKej\NotificationPreferences\NotificationPreferencesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            // Set up notification preferences config
            $config->set('notification-preferences.notifications', [
                'TestNotificationWithDefaults' => TestNotificationWithDefaults::class,
                'TestNotificationWithoutDefaults' => TestNotificationWithoutDefaults::class,
                'TestNotificationWithRestrictedAvailableChannels' => TestNotificationWithRestrictedAvailableChannels::class, // Add new notification
            ]);
            $config->set('notification-preferences.channels', [
                'mail',
                'database',
                'slack',
            ]);
            $config->set('notification-preferences.default_channels', [
                'slack',
            ]);
        });
    }

    /** @test */
    public function test_user_can_set_and_get_notification_preferences()
    {
        /** @var User $user */
        $user = new \Workbench\Database\Factories\UserFactory()->create();

        $preferences = [
            'TestNotificationWithDefaults' => ['mail', 'database'],
            'TestNotificationWithoutDefaults' => ['slack'],
        ];

        $user->notification_preferences = $preferences;
        $user->save();

        $retrievedPreferences = $user->getNotificationPreferences()->toPreferenceMap();

        $storedPreferences = json_decode($user->getRawOriginal('notification_preferences'), true);
        $this->assertEquals($preferences, $storedPreferences);

        $expectedRetrievedPreferences = [
            'TestNotificationWithDefaults' => [
                'mail' => true,
                'database' => true,
                'slack' => null,
            ],
            'TestNotificationWithoutDefaults' => [
                'mail' => null,
                'database' => null,
                'slack' => true,
            ],
            'TestNotificationWithRestrictedAvailableChannels' => [
                'mail' => null,
                'database' => null,
                'slack' => null,
            ],
        ];

        $this->assertEquals($expectedRetrievedPreferences, $retrievedPreferences);
    }

    /** @test */
    public function test_notification_is_routed_based_on_user_preferences()
    {
        Notification::fake();

        /** @var User $user */
        $user = new \Workbench\Database\Factories\UserFactory()->create();

        // User prefers to receive TestNotificationWithDefaults only via slack
        $user->notification_preferences = [
            'TestNotificationWithDefaults' => ['slack'],
        ];
        $user->save();

        // Send the notification
        $user->notify(new TestNotificationWithDefaults());

        // Assert it was only sent to slack
        Notification::assertSentTo(
            $user,
            TestNotificationWithDefaults::class,
            function (TestNotificationWithDefaults $notification, array $channels) {
                $this->assertCount(1, $channels);
                $this->assertEquals('slack', $channels[0]);
                return true;
            }
        );
    }

    /** @test */
    public function test_notification_with_own_defaults_is_routed_to_its_defaults_when_user_has_no_preference()
    {
        Notification::fake();

        /** @var User $user */
        $user = new \Workbench\Database\Factories\UserFactory()->create();

        // Send the notification that has its own default channels
        $user->notify(new TestNotificationWithDefaults());

        // Assert it was sent to its defined default channels ('mail', 'database')
        Notification::assertSentTo(
            $user,
            TestNotificationWithDefaults::class,
            function ($notification, $channels) {
                $this->assertCount(2, $channels);
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);
                $this->assertNotContains('slack', $channels);
                return true;
            });
    }

    /** @test */
    public function test_notification_without_own_defaults_is_routed_to_global_defaults_when_user_has_no_preference()
    {
        Notification::fake();

        /** @var User $user */
        $user = new \Workbench\Database\Factories\UserFactory()->create();

        // Send the notification that does not have its own default channels
        $user->notify(new TestNotificationWithoutDefaults());

        $manager = app(\KejKej\NotificationPreferences\Contracts\NotificationConfigurator::class);
        $defaultChannels = $manager->defaultChannels();

        Notification::assertSentTo(
            $user,
            TestNotificationWithoutDefaults::class,
            function ($notification, $channels) use ($defaultChannels) {
                $this->assertCount(count($defaultChannels), $channels);
                $this->assertEquals($defaultChannels, $channels);
                return true;
            });
    }

    /** @test */
    public function notification_respects_its_own_available_channels_even_if_user_prefers_unavailable_one()
    {
        Notification::fake();

        /** @var User $user */
        $user = User::factory()->create();

        // User prefers 'database' and 'slack'. 'database' is NOT in TestNotificationWithRestrictedAvailableChannels's $availableChannels.
        $user->notification_preferences = [
            'TestNotificationWithRestrictedAvailableChannels' => ['database', 'slack'],
        ];
        $user->save();

        $user->notify(new TestNotificationWithRestrictedAvailableChannels());

        // Should only be sent to 'slack' because 'database' is not available for this specific notification.
        Notification::assertSentTo($user, TestNotificationWithRestrictedAvailableChannels::class, function ($notification, $channels) {
            $this->assertCount(1, $channels);
            $this->assertEquals('slack', $channels[0]);
            $this->assertNotContains('database', $channels);
            $this->assertNotContains('mail', $channels); // 'mail' is available and default, but user didn't select it.
            return true;
        });
    }

    /** @test */
    public function notification_uses_its_own_default_channel_if_user_preference_is_not_available()
    {
        Notification::fake();

        /** @var User $user */
        $user = User::factory()->create();

        // User prefers 'database', which is NOT in TestNotificationWithRestrictedAvailableChannels's $availableChannels.
        $user->notification_preferences = [
            'TestNotificationWithRestrictedAvailableChannels' => ['database'],
        ];
        $user->save();

        $user->notify(new TestNotificationWithRestrictedAvailableChannels());

        // User has explicit preference, but none of selected channels are available for this notification.
        Notification::assertNotSentTo($user, TestNotificationWithRestrictedAvailableChannels::class);
    }

    /** @test */
    public function adding_new_notification_type_after_user_has_saved_preferences_builds_complete_matrix()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $user->notification_preferences = [
            'TestNotificationWithDefaults' => ['mail'],
        ];
        $user->save();

        config()->set('notification-preferences.notifications', [
            ...config('notification-preferences.notifications'),
            'NewlyAddedNotificationType' => TestNotificationWithDefaults::class,
        ]);

        $user->refresh();
        $preferences = $user->getNotificationPreferences()->toPreferenceMap();

        $this->assertArrayHasKey('NewlyAddedNotificationType', $preferences);
        $this->assertEquals([
            'mail' => null,
            'database' => null,
            'slack' => null,
        ], $preferences['NewlyAddedNotificationType']);

        $this->assertTrue($preferences['TestNotificationWithDefaults']['mail']);
        $this->assertFalse($preferences['TestNotificationWithDefaults']['database']);
        $this->assertFalse($preferences['TestNotificationWithDefaults']['slack']);
    }

    /** @test */
    public function unknown_notification_and_channels_are_dropped_on_write()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $user->notification_preferences = [
            'TestNotificationWithDefaults' => ['mail', 'invalid-channel', 'mail'],
            'UnknownNotification' => ['mail'],
        ];
        $user->save();

        $storedPreferences = json_decode($user->getRawOriginal('notification_preferences'), true);

        $this->assertEquals([
            'TestNotificationWithDefaults' => ['mail'],
        ], $storedPreferences);
    }

    /** @test */
    public function legacy_unknown_data_is_ignored_on_read_but_preserved_until_rewrite()
    {
        /** @var User $user */
        $user = User::factory()->create();

        User::query()->whereKey($user->id)->update([
            'notification_preferences' => json_encode([
                'UnknownNotification' => ['mail'],
                'TestNotificationWithDefaults' => ['mail', 'unknown-channel'],
            ]),
        ]);

        $user->refresh();
        $preferences = $user->getNotificationPreferences()->toPreferenceMap();

        $this->assertArrayNotHasKey('UnknownNotification', $preferences);
        $this->assertTrue($preferences['TestNotificationWithDefaults']['mail']);
        $this->assertFalse($preferences['TestNotificationWithDefaults']['database']);
        $this->assertFalse($preferences['TestNotificationWithDefaults']['slack']);

        $rawStored = json_decode($user->getRawOriginal('notification_preferences'), true);
        $this->assertArrayHasKey('UnknownNotification', $rawStored);
        $this->assertContains('unknown-channel', $rawStored['TestNotificationWithDefaults']);
    }
}
