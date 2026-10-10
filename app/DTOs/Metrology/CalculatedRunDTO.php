<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use Brick\Math\BigDecimal;

final class CalculatedRunDTO
{
    public function __construct(
        public readonly int $runNumber,
        public readonly int $fillNumber,
        public readonly BigDecimal $indicatedVolume,
        public readonly BigDecimal $gaugeTemperature,
        public readonly BigDecimal $proverTemperature,
        public readonly BigDecimal $proverPressure,
        public readonly BigDecimal $cTdw,
        public readonly BigDecimal $cTsm,
        public readonly BigDecimal $cTsp,
        public readonly BigDecimal $cPsp,
        public readonly BigDecimal $cPlp,
        public readonly BigDecimal $correctedVolume,
        public readonly ?BigDecimal $scaleReadingMm = null,
        public readonly ?BigDecimal $shaftTemperature = null
    ) {}
}
