<?php

declare(strict_types=1);

namespace App\Enums;

enum FluidType: string
{
    case Liquid = 'liquid';
    case Gas = 'gas';

    /**
     * Get the translated label for the fluid type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Liquid => __('Liquid'),
            self::Gas => __('Gas'),
        };
    }

    /**
     * Get the semantic badge variant for the fluid type.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Liquid => 'info',
            self::Gas => 'warning',
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
            'liquid' => self::Liquid,
            'gas' => self::Gas,
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
