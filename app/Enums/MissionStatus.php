<?php

declare(strict_types=1);

namespace App\Enums;

enum MissionStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
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
            self::Planned => 'warning',
            self::Active => 'success',
            self::Completed => 'info',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Get the semantic color token for indicators and charts.
     */
    public function color(): string
    {
        return match ($this) {
            self::Planned => 'amber',
            self::Active => 'emerald',
            self::Completed => 'blue',
            self::Cancelled => 'rose',
        };
    }

    /**
     * Determine whether the mission can be edited or deleted.
     */
    public function isModifiable(): bool
    {
        return $this === self::Planned;
    }

    /**
     * Determine whether the mission can be activated.
     */
    public function canActivate(): bool
    {
        return $this === self::Planned;
    }

    /**
     * Determine whether the mission can be marked completed.
     */
    public function canComplete(): bool
    {
        return $this === self::Active;
    }

    /**
     * Determine whether the mission can be reverted back to planned.
     */
    public function canRevert(): bool
    {
        return $this === self::Active || $this === self::Completed;
    }
}
