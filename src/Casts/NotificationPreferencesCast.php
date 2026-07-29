<?php

namespace KejKej\NotificationPreferences\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use KejKej\NotificationPreferences\Services\NotificationRegistry;

/**
 * @implements CastsAttributes<array<string, list<string>>, array<string, list<string>>>
 */
final class NotificationPreferencesCast implements CastsAttributes
{
    /**
     * @return array<string, list<string>>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            try {
                $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new \UnexpectedValueException(
                    'The stored notification preferences are not valid JSON.',
                    previous: $exception,
                );
            }
        }

        if (! is_array($value)) {
            throw new \UnexpectedValueException('Notification preferences must be stored as a JSON object.');
        }

        return app(NotificationRegistry::class)->normalizeStoredPreferences($value, strict: false);
    }

    /**
     * @param  array<string, list<string>>  $value
     * @return array<string, string>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $normalized = app(NotificationRegistry::class)->normalizeStoredPreferences($value, strict: true);

        return [
            $key => json_encode($normalized, JSON_THROW_ON_ERROR),
        ];
    }
}
