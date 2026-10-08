<?php

declare(strict_types=1);

namespace App\Enums;

enum InstrumentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
    case Decommissioned = 'decommissioned';

    /**
     * Get the translated label for the instrument status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Inactive => __('Inactive'),
            self::Maintenance => __('Maintenance'),
            self::Decommissioned => __('Decommissioned'),
        };
    }

    /**
     * Get the semantic badge variant for the instrument status.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
            self::Maintenance => 'warning',
            self::Decommissioned => 'neutral',
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
