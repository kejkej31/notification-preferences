<?php

namespace KejKej\NotificationPreferences\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use KejKej\NotificationPreferences\DTO\NotificationPreferenceRow;
use KejKej\NotificationPreferences\DTO\NotificationPreferencesMatrix;

class NotificationPreferencesMatrixDtoTest extends TestCase
{
    #[Test]
    public function row_exposes_enabled_disabled_and_unset_channels(): void
    {
        $row = new NotificationPreferenceRow(
            notification: 'PostCommented',
            channels: [
                'mail' => true,
                'database' => false,
                'slack' => null,
            ],
        );

        $this->assertSame(['mail'], $row->enabledChannels());
        $this->assertSame(['database'], $row->disabledChannels());
        $this->assertSame(['slack'], $row->unsetChannels());
    }

    #[Test]
    public function matrix_returns_rows_and_selected_channels_for_notification(): void
    {
        $matrix = new NotificationPreferencesMatrix([
            'PostCommented' => new NotificationPreferenceRow('PostCommented', [
                'mail' => true,
                'database' => false,
            ]),
            'NewFollower' => new NotificationPreferenceRow('NewFollower', [
                'mail' => null,
                'database' => null,
            ]),
        ]);

        $this->assertNotNull($matrix->row('PostCommented'));
        $this->assertNull($matrix->row('MissingNotification'));

        $this->assertSame(['mail'], $matrix->selectedChannelsFor('PostCommented'));
        $this->assertSame([], $matrix->selectedChannelsFor('NewFollower'));
        $this->assertNull($matrix->selectedChannelsFor('MissingNotification'));

        $this->assertSame([
            'PostCommented' => [
                'mail' => true,
                'database' => false,
            ],
            'NewFollower' => [
                'mail' => null,
                'database' => null,
            ],
        ], $matrix->toPreferenceMap());
    }
}
