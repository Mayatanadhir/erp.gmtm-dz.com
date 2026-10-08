<?php

declare(strict_types=1);

namespace App\Enums;

enum ProverType: string
{
    case BidirectionalPipe = 'bidirectional_pipe';
    case UnidirectionalPipe = 'unidirectional_pipe';
    case CompactSvp = 'compact_svp';

    /**
     * Get the translated label for the prover type.
     */
    public function label(): string
    {
        return match ($this) {
            self::BidirectionalPipe => __('Bidirectional Pipe Prover'),
            self::UnidirectionalPipe => __('Unidirectional Pipe Prover'),
            self::CompactSvp => __('Compact SVP (Small Volume Prover)'),
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
