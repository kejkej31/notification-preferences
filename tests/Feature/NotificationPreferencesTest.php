<?php

namespace KejKej\NotificationPreferences\Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use KejKej\NotificationPreferences\NotificationPreferencesServiceProvider;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\User;
use Workbench\App\Notifications\TestNotificationNewType;
use Workbench\App\Notifications\TestNotificationWithDefaults;
use Workbench\App\Notifications\TestNotificationWithoutDefaults;
use Workbench\App\Notifications\TestNotificationWithRestrictedAvailableChannels;
use Workbench\Database\Factories\UserFactory;

#[WithMigration]
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(): User
    {
        /** @var User $user */
        $user = new UserFactory()->create();

        return $user;
    }

    protected function getPackageProviders($app)
    {
        return [
            NotificationPreferencesServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('notification-preferences.notifications', [
                'with-defaults' => [
                    'class' => TestNotificationWithDefaults::class,
                    'channels' => ['mail', 'database', 'slack'],
                    'default_channels' => ['mail', 'database'],
                ],
                'without-defaults' => [
                    'class' => TestNotificationWithoutDefaults::class,
                    'channels' => ['mail', 'database', 'slack'],
                    'default_channels' => ['slack'],
                ],
                'restricted' => [
                    'class' => TestNotificationWithRestrictedAvailableChannels::class,
                    'channels' => ['mail', 'slack'],
                    'default_channels' => ['mail'],
                ],
            ]);
        });
    }

    #[Test]
    public function user_can_set_and_get_symmetric_notification_preferences(): void
    {
        $user = $this->createUser();

        $preferences = [
            'with-defaults' => ['mail', 'database'],
            'without-defaults' => ['slack'],
        ];

        $user->notification_preferences = $preferences;
        $user->save();
        $user->refresh();

        $this->assertSame($preferences, $user->notification_preferences);
        $this->assertSame($preferences['with-defaults'], $user->getNotificationPreference('with-defaults'));
        $this->assertNull($user->getNotificationPreference('restricted'));

        $storedPreferences = json_decode($user->getRawOriginal('notification_preferences'), true);
        $this->assertSame($preferences, $storedPreferences);
    }

    #[Test]
    public function preference_options_expose_selected_and_effective_channels(): void
    {
        $user = $this->createUser();
        $user->notification_preferences = ['with-defaults' => ['mail']];

        $this->assertSame([
            'channels' => ['mail', 'database', 'slack'],
            'default_channels' => ['mail', 'database'],
            'selected_channels' => ['mail'],
            'effective_channels' => ['mail'],
        ], $user->getNotificationPreferences()['with-defaults']);

        $this->assertSame([
            'channels' => ['mail', 'database', 'slack'],
            'default_channels' => ['slack'],
            'selected_channels' => null,
            'effective_channels' => ['slack'],
        ], $user->getNotificationPreferences()['without-defaults']);
    }

    #[Test]
    public function explicit_user_preferences_control_routing(): void
    {
        Notification::fake();
        $user = $this->createUser();
        $user->notification_preferences = ['with-defaults' => ['slack']];
        $user->save();

        $user->notify(new TestNotificationWithDefaults);

        Notification::assertSentTo($user, TestNotificationWithDefaults::class, function ($notification, array $channels) {
            $this->assertSame(['slack'], $channels);

            return true;
        });
    }

    #[Test]
    public function configured_defaults_are_used_when_user_has_no_preference(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $user->notify(new TestNotificationWithDefaults);

        Notification::assertSentTo($user, TestNotificationWithDefaults::class, function ($notification, array $channels) {
            $this->assertSame(['mail', 'database'], $channels);

            return true;
        });
    }

    #[Test]
    public function explicit_empty_preferences_disable_all_channels(): void
    {
        Notification::fake();
        $user = $this->createUser();
        $user->notification_preferences = ['with-defaults' => []];
        $user->save();

        $user->notify(new TestNotificationWithDefaults);

        Notification::assertNotSentTo($user, TestNotificationWithDefaults::class);
    }

    #[Test]
    public function selected_channels_are_filtered_by_the_definition(): void
    {
        Notification::fake();
        $user = $this->createUser();
        $user->notification_preferences = ['restricted' => ['slack']];
        $user->save();

        $user->notify(new TestNotificationWithRestrictedAvailableChannels);

        Notification::assertSentTo($user, TestNotificationWithRestrictedAvailableChannels::class, function ($notification, array $channels) {
            $this->assertSame(['slack'], $channels);

            return true;
        });
    }

    #[Test]
    public function an_explicit_preference_with_no_available_channels_sends_nothing(): void
    {
        Notification::fake();
        $user = $this->createUser();
        $user->notification_preferences = ['restricted' => []];
        $user->save();

        $user->notify(new TestNotificationWithRestrictedAvailableChannels);

        Notification::assertNotSentTo($user, TestNotificationWithRestrictedAvailableChannels::class);
    }

    #[Test]
    public function adding_a_notification_definition_does_not_require_data_migration(): void
    {
        $user = $this->createUser();
        $user->notification_preferences = ['with-defaults' => ['mail']];
        $user->save();

        config()->set('notification-preferences.notifications.new-type', [
            'class' => TestNotificationNewType::class,
            'channels' => ['mail'],
            'default_channels' => ['mail'],
        ]);

        $user->refresh();
        $this->assertSame(['mail'], $user->getNotificationPreferences()['with-defaults']['selected_channels']);
        $this->assertArrayHasKey('new-type', $user->getNotificationPreferences());
    }

    #[Test]
    public function invalid_notification_keys_and_channels_are_rejected_on_write(): void
    {
        $user = $this->createUser();

        $this->expectException(\InvalidArgumentException::class);
        $user->notification_preferences = [
            'unknown' => ['mail'],
        ];
    }

    #[Test]
    public function malformed_channel_values_are_rejected_on_write(): void
    {
        $user = $this->createUser();

        $this->expectException(\InvalidArgumentException::class);
        $user->notification_preferences = [
            'with-defaults' => ['mail', 123],
        ];
    }

    #[Test]
    public function obsolete_stored_entries_are_ignored_without_rewriting_raw_data(): void
    {
        $user = $this->createUser();
        User::query()->whereKey($user->id)->update([
            'notification_preferences' => json_encode([
                'obsolete' => ['mail'],
                'with-defaults' => ['mail', 'removed-channel'],
            ]),
        ]);

        $user->refresh();

        $this->assertSame(['mail'], $user->notification_preferences['with-defaults']);
        $rawStored = json_decode($user->getRawOriginal('notification_preferences'), true);
        $this->assertArrayHasKey('obsolete', $rawStored);
        $this->assertContains('removed-channel', $rawStored['with-defaults']);
    }

    #[Test]
    public function malformed_persisted_json_is_rejected_on_read(): void
    {
        $user = $this->createUser();
        User::query()->whereKey($user->id)->update(['notification_preferences' => '{not-json']);
        $user->refresh();

        $this->expectException(\UnexpectedValueException::class);
        $user->getNotificationPreferences();
    }
}
