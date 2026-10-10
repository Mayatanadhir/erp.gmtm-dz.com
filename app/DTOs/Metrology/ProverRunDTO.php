<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use Brick\Math\BigDecimal;

final class ProverRunDTO
{
    public function __construct(
        public readonly int $runNumber,
        public readonly int $fillNumber,
        public readonly BigDecimal $gaugeTemperature,
        public readonly BigDecimal $proverTemperature,
        public readonly BigDecimal $proverPressure,
        public readonly ?BigDecimal $indicatedVolume = null,
        public readonly ?BigDecimal $shaftTemperature = null,
        public readonly ?BigDecimal $scaleReadingMm = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $indicatedVolume = isset($data['indicated_volume']) && $data['indicated_volume'] !== '' && $data['indicated_volume'] !== null
            ? BigDecimal::of((string) $data['indicated_volume'])
            : null;

        $scaleReadingMm = isset($data['scale_reading_mm']) && $data['scale_reading_mm'] !== '' && $data['scale_reading_mm'] !== null
            ? BigDecimal::of((string) $data['scale_reading_mm'])
            : null;

        $shaftTemp = isset($data['shaft_temperature']) && $data['shaft_temperature'] !== '' && $data['shaft_temperature'] !== null
            ? BigDecimal::of((string) $data['shaft_temperature'])
            : null;

        return new self(
            runNumber: (int) ($data['run_number'] ?? 1),
            fillNumber: (int) ($data['fill_number'] ?? 1),
            gaugeTemperature: BigDecimal::of((string) ($data['gauge_temperature'] ?? '20.00')),
            proverTemperature: BigDecimal::of((string) ($data['prover_temperature'] ?? '20.00')),
            proverPressure: BigDecimal::of((string) ($data['prover_pressure'] ?? '0.0000')),
            indicatedVolume: $indicatedVolume,
            shaftTemperature: $shaftTemp,
            scaleReadingMm: $scaleReadingMm,
        );
    }
}
