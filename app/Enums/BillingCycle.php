<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingCycle: string
{
    case Annuelle = 'annuelle';
    case Semestrielle = 'semestrielle';

    public function label(): string
    {
        return match ($this) {
            self::Annuelle => 'Annuelle',
            self::Semestrielle => 'Semestrielle',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Annuelle => 'info',
            self::Semestrielle => 'warning',
        };
    }
}
