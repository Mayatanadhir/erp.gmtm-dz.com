<?php

declare(strict_types=1);

namespace App\Enums;

enum ExpenseChargeType: string
{
    case Fixed = 'fixed';
    case Variable = 'variable';

    /**
     * Human-readable label for the charge classification.
     */
    public function label(): string
    {
        return match ($this) {
            self::Fixed => __('Fixed Cost'),
            self::Variable => __('Variable Cost'),
        };
    }

    /**
     * Semantic badge color variant for the UI Design System.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Fixed => 'primary',
            self::Variable => 'warning',
        };
    }
}
