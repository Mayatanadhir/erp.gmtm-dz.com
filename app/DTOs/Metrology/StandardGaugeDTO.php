<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use App\Constants\MetrologyConstants;
use App\Models\Instrument;
use Brick\Math\BigDecimal;

final class StandardGaugeDTO
{
    public function __construct(
        public readonly ?string $serialNumber,
        public readonly BigDecimal $nominalVolume,
        public readonly BigDecimal $thermalExpansion,
        public readonly ?BigDecimal $neckScaleFactor = null,
    ) {}

    /**
     * Build directly from Eloquent Instrument and its standardGaugeSpecification.
     */
    public static function fromInstrument(Instrument $instrument): self
    {
        $spec = $instrument->standardGaugeSpecification;

        $thermalExpansion = $spec?->cubical_expansion_coef_gcm
            ?? MetrologyConstants::G_CM_STAINLESS_STEEL;

        $neckScaleFactor = $spec?->neck_scale_sensitivity ?? null;

        return new self(
            serialNumber: $instrument->serial_number ?? null,
            nominalVolume: BigDecimal::of((string) ($spec?->nominal_capacity_liters ?? '0')),
            thermalExpansion: BigDecimal::of((string) $thermalExpansion),
            neckScaleFactor: $neckScaleFactor !== null ? BigDecimal::of((string) $neckScaleFactor) : null,
        );
    }

    /**
     * Build from array data (used in live preview).
     */
    public static function fromArray(array $data): self
    {
        $thermalExpansion = ! empty($data['gauge_thermal_expansion'])
            ? (string) $data['gauge_thermal_expansion']
            : (! empty($data['cubical_expansion_coef_gcm']) ? (string) $data['cubical_expansion_coef_gcm'] : MetrologyConstants::G_CM_STAINLESS_STEEL);

        $neckFactor = ! empty($data['gauge_neck_scale_factor'])
            ? (string) $data['gauge_neck_scale_factor']
            : (! empty($data['neck_scale_sensitivity']) ? (string) $data['neck_scale_sensitivity'] : null);

        $volume = $data['gauge_nominal_volume'] ?? $data['nominal_capacity_liters'] ?? '0';

        return new self(
            serialNumber: isset($data['gauge_serial_number']) ? (string) $data['gauge_serial_number'] : ($data['serial_number'] ?? null),
            nominalVolume: BigDecimal::of((string) $volume),
            thermalExpansion: BigDecimal::of($thermalExpansion),
            neckScaleFactor: $neckFactor !== null ? BigDecimal::of((string) $neckFactor) : null,
        );
    }
}
