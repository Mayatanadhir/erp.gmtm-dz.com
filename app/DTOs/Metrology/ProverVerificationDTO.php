<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use App\Constants\MetrologyConstants;
use Brick\Math\BigDecimal;

final class ProverVerificationDTO
{
    /**
     * @param  array<int, ProverRunDTO>  $runs
     */
    public function __construct(
        public readonly string $calibrationDate,
        public readonly BigDecimal $referenceTemperature,
        public readonly ?string $remarks,
        public readonly ProverTubeDTO $proverTube,
        public readonly StandardGaugeDTO $standardGauge,
        public readonly array $runs,
        public readonly string $pressureUnit = 'bar'
    ) {}

    /**
     * Build from array data (used in live preview).
     */
    public static function fromArray(array $data): self
    {
        $refTemp = ! empty($data['reference_temperature'])
            ? (string) $data['reference_temperature']
            : MetrologyConstants::REF_TEMPERATURE_C;

        $runs = [];
        if (! empty($data['runs']) && is_array($data['runs'])) {
            foreach ($data['runs'] as $runData) {
                if (is_array($runData)) {
                    $runs[] = ProverRunDTO::fromArray($runData);
                }
            }
        }

        return new self(
            calibrationDate: (string) ($data['calibration_date'] ?? date('Y-m-d')),
            referenceTemperature: BigDecimal::of($refTemp),
            remarks: isset($data['remarks']) ? (string) $data['remarks'] : null,
            proverTube: ProverTubeDTO::fromArray($data),
            standardGauge: StandardGaugeDTO::fromArray($data),
            runs: $runs,
            pressureUnit: (string) ($data['pressure_unit'] ?? 'bar')
        );
    }

    /**
     * Build from session data and Eloquent DTOs.
     *
     * @param  array<int, ProverRunDTO>|null  $runs
     */
    public static function fromArrayWithDTOs(
        array $data,
        ProverTubeDTO $proverTube,
        StandardGaugeDTO $standardGauge,
        ?array $runs = null
    ): self {
        $refTemp = ! empty($data['reference_temperature'])
            ? (string) $data['reference_temperature']
            : MetrologyConstants::REF_TEMPERATURE_C;

        if ($runs === null) {
            $runs = [];
            if (! empty($data['runs']) && is_array($data['runs'])) {
                foreach ($data['runs'] as $runData) {
                    if (is_array($runData)) {
                        $runs[] = ProverRunDTO::fromArray($runData);
                    }
                }
            }
        }

        return new self(
            calibrationDate: (string) ($data['calibration_date'] ?? date('Y-m-d')),
            referenceTemperature: BigDecimal::of($refTemp),
            remarks: isset($data['remarks']) ? (string) $data['remarks'] : null,
            proverTube: $proverTube,
            standardGauge: $standardGauge,
            runs: $runs,
            pressureUnit: (string) ($data['pressure_unit'] ?? 'bar')
        );
    }
}
