<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseAffiliation: string
{
    case Mission = 'mission';
    case Contract = 'contract';
    case Item = 'item';
    case Gmtm = 'gmtm';
    case Prisma = 'prisma';

    /**
     * Human-readable label for the expense affiliation.
     */
    public function label(): string
    {
        return match ($this) {
            self::Mission => __('Mission'),
            self::Contract => __('Contract'),
            self::Item => __('Contract Item'),
            self::Gmtm => __('GMTM Corporate'),
            self::Prisma => __('Prisma Internal'),
        };
    }

    /**
     * Semantic badge color variant for the UI Design System.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Mission => 'primary',
            self::Contract => 'info',
            self::Item => 'warning',
            self::Gmtm => 'neutral',
            self::Prisma => 'danger',
        };
    }

    /**
     * Tool icon name or SVG representation.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Mission => 'fa-tasks',
            self::Contract => 'fa-file-contract',
            self::Item => 'fa-cube',
            self::Gmtm => 'fa-building',
            self::Prisma => 'fa-layer-group',
        };
    }
}
