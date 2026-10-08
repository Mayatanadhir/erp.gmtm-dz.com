<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessVariable: string
{
    case Pressure = 'pressure';
    case Temperature = 'temperature';
    case Flow = 'flow';
    case Level = 'level';
    case Quality = 'quality';
    case Volume = 'volume';

    /**
     * Get the translated label for the process variable.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pressure => __('Pressure'),
            self::Temperature => __('Temperature'),
            self::Flow => __('Flow'),
            self::Level => __('Level'),
            self::Quality => __('Quality / Composition'),
            self::Volume => __('Volume'),
        };
    }

    /**
     * Get the semantic badge variant for the process variable.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pressure => 'primary',
            self::Temperature => 'warning',
            self::Flow => 'info',
            self::Level => 'neutral',
            self::Quality => 'secondary',
            self::Volume => 'success',
        };
    }

    /**
     * Normalize legacy string to enum instance.
     */
    public static function tryFromLegacy(?string $value): ?self
    {
        if (blank($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            'pressure' => self::Pressure,
            'temperature' => self::Temperature,
            'flow' => self::Flow,
            'level' => self::Level,
            'quality' => self::Quality,
            'volume' => self::Volume,
            default => self::tryFrom($value),
        };
    }

    /**
     * Get all enum values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
