<?php

declare(strict_types=1);

namespace App\Enums;

enum MissionDeploymentStatus: string
{
    case Active = 'active';
    case Returned = 'returned';
    case Damaged = 'damaged';

    /**
     * Get the human-readable label for the deployment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Returned => __('Returned'),
            self::Damaged => __('Damaged'),
        };
    }

    /**
     * Get the semantic badge variant for the design system (<x-badge>).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Returned => 'info',
            self::Damaged => 'danger',
        };
    }

    /**
     * Get the semantic color token.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Returned => 'blue',
            self::Damaged => 'rose',
        };
    }
}
