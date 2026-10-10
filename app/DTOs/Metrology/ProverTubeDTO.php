<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use App\Constants\MetrologyConstants;
use App\Models\Instrument;
use BackedEnum;
use Brick\Math\BigDecimal;

final class ProverTubeDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $serialNumber,
        public readonly ?string $tag,
        public readonly BigDecimal $diameter,
        public readonly BigDecimal $thickness,
        public readonly BigDecimal $thermalExpansion,
        public readonly BigDecimal $elasticityModulus,
        public readonly ?BigDecimal $areaExpansion = null,
        public readonly ?BigDecimal $linearExpansion = null,
        public readonly ?BigDecimal $nominalBaseVolume = null
    ) {}

    /**
     * Build directly from Eloquent Instrument and its proverSpecification.
     */
    public static function fromInstrument(Instrument $instrument): self
    {
        $spec = $instrument->proverSpecification;

        $rawType = $spec?->type ?? $spec?->prover_type ?? 'bidirectional_pipe';
        $type = $rawType instanceof BackedEnum ? (string) $rawType->value : (string) $rawType;

        $thermalExpansion = $spec?->cubical_expansion_coef
            ?? $spec?->thermal_expansion
            ?? MetrologyConstants::G_C_MILD_STEEL;

        $elasticityModulus = $spec?->elasticity_modulus
            ?? MetrologyConstants::E_MODULUS_STEEL_BAR;

        $diameter = $spec?->inner_diameter ?? $spec?->diameter ?? '0';
        $thickness = $spec?->wall_thickness ?? $spec?->thickness ?? '0';

        $areaExpansion = $spec?->area_expansion_coef
            ?? ($type === 'compact_svp' ? MetrologyConstants::G_A_SVP_CYLINDER : null);

        $linearExpansion = $spec?->linear_expansion_coef
            ?? ($type === 'compact_svp' ? MetrologyConstants::G_L_SVP_SHAFT : null);

        $nominalBaseVolume = $spec?->nominal_base_volume !== null
            ? BigDecimal::of((string) $spec->nominal_base_volume)
            : null;

        return new self(
            type: $type,
            serialNumber: $instrument->serial_number ?? 'UNKNOWN-PROVER',
            tag: $instrument->tag_number ?? null,
            diameter: BigDecimal::of((string) $diameter),
            thickness: BigDecimal::of((string) $thickness),
            thermalExpansion: BigDecimal::of((string) $thermalExpansion),
            elasticityModulus: BigDecimal::of((string) $elasticityModulus),
            areaExpansion: $areaExpansion !== null ? BigDecimal::of((string) $areaExpansion) : null,
            linearExpansion: $linearExpansion !== null ? BigDecimal::of((string) $linearExpansion) : null,
            nominalBaseVolume: $nominalBaseVolume,
        );
    }

    /**
     * Build from array data (used in live preview).
     */
    public static function fromArray(array $data): self
    {
        $type = (string) ($data['prover_type'] ?? $data['type'] ?? 'bidirectional_pipe');

        $thermalExpansion = ! empty($data['prover_thermal_expansion'])
            ? (string) $data['prover_thermal_expansion']
            : (! empty($data['cubical_expansion_coef']) ? (string) $data['cubical_expansion_coef'] : MetrologyConstants::G_C_MILD_STEEL);

        $elasticityModulus = ! empty($data['prover_elasticity_modulus'])
            ? (string) $data['prover_elasticity_modulus']
            : (! empty($data['elasticity_modulus']) ? (string) $data['elasticity_modulus'] : MetrologyConstants::E_MODULUS_STEEL_BAR);

        $areaExpansion = ! empty($data['prover_area_expansion'])
            ? BigDecimal::of((string) $data['prover_area_expansion'])
            : (! empty($data['area_expansion_coef'])
                ? BigDecimal::of((string) $data['area_expansion_coef'])
                : ($type === 'compact_svp' ? BigDecimal::of(MetrologyConstants::G_A_SVP_CYLINDER) : null));

        $linearExpansion = ! empty($data['prover_linear_expansion'])
            ? BigDecimal::of((string) $data['prover_linear_expansion'])
            : (! empty($data['linear_expansion_coef'])
                ? BigDecimal::of((string) $data['linear_expansion_coef'])
                : ($type === 'compact_svp' ? BigDecimal::of(MetrologyConstants::G_L_SVP_SHAFT) : null));

        $nominalBaseVolume = ! empty($data['prover_nominal_base_volume'])
            ? BigDecimal::of((string) $data['prover_nominal_base_volume'])
            : (! empty($data['nominal_base_volume'])
                ? BigDecimal::of((string) $data['nominal_base_volume'])
                : (! empty($data['previous_bpv'])
                    ? BigDecimal::of((string) $data['previous_bpv'])
                    : null));

        $diameter = $data['prover_diameter'] ?? $data['inner_diameter'] ?? '0';
        $thickness = $data['prover_thickness'] ?? $data['wall_thickness'] ?? '0';

        return new self(
            type: $type,
            serialNumber: (string) ($data['prover_serial_number'] ?? $data['serial_number'] ?? 'UNKNOWN-PROVER'),
            tag: isset($data['prover_tag']) ? (string) $data['prover_tag'] : ($data['tag_number'] ?? null),
            diameter: BigDecimal::of((string) $diameter),
            thickness: BigDecimal::of((string) $thickness),
            thermalExpansion: BigDecimal::of($thermalExpansion),
            elasticityModulus: BigDecimal::of($elasticityModulus),
            areaExpansion: $areaExpansion,
            linearExpansion: $linearExpansion,
            nominalBaseVolume: $nominalBaseVolume,
        );
    }
}
