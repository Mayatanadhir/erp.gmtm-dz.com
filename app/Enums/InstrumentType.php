<?php

declare(strict_types=1);

namespace App\Enums;

enum InstrumentType: string
{
    case Transmitter = 'transmitter';
    case FlowComputer = 'flow_computer';
    case Probe = 'probe';
    case Chromatograph = 'chromatograph';
    case StandardGauge = 'standard_gauge';
    case Prover = 'prover';

    /**
     * Get the translated label for the instrument type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Transmitter => __('Transmitter'),
            self::FlowComputer => __('Flow Computer'),
            self::Probe => __('Probe (RTD)'),
            self::Chromatograph => __('Gas Chromatograph'),
            self::StandardGauge => __('Standard Gauge / Jauge Étalon'),
            self::Prover => __('Prover (Pipe / SVP)'),
        };
    }

    /**
     * Get the semantic badge variant for the instrument type.
     */
    public function badgeVariant(): string
    {
        return match ($this) {
            self::Transmitter => 'info',
            self::FlowComputer => 'primary',
            self::Probe => 'success',
            self::Chromatograph => 'neutral',
            self::StandardGauge => 'warning',
            self::Prover => 'info',
        };
    }

    /**
     * Get the FontAwesome / Tool icon name for the instrument type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Transmitter => 'fa-satellite-dish',
            self::FlowComputer => 'fa-server',
            self::Probe => 'fa-thermometer-half',
            self::Chromatograph => 'fa-vial',
            self::StandardGauge => 'fa-flask',
            self::Prover => 'fa-tachometer-alt',
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
            'transmitter' => self::Transmitter,
            'flowcomputer', 'flow_computer' => self::FlowComputer,
            'probe' => self::Probe,
            'chromatograph' => self::Chromatograph,
            'standardgauge', 'standard_gauge', 'testmeasure', 'test_measure', 'guge_etalon' => self::StandardGauge,
            'prover' => self::Prover,
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
