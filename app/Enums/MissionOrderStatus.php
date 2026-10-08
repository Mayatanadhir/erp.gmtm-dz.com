<?php

declare(strict_types=1);

namespace App\Enums;

enum MissionOrderStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the order status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * Get the semantic badge variant for the design system (<x-badge>).
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Completed => 'info',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Get the semantic color token.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Completed => 'blue',
            self::Cancelled => 'rose',
        };
    }
}
