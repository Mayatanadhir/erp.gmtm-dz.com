<?php

declare(strict_types=1);

namespace App\DTOs\Metrology;

use Brick\Math\BigDecimal;

final class MetrologyCalculationResultDTO
{
    /**
     * @param  array<int, CalculatedRunDTO>  $calculatedRuns
     * @param  array<int, BigDecimal>  $runAggregatedVolumes
     */
    public function __construct(
        public readonly array $calculatedRuns,
        public readonly array $runAggregatedVolumes,
        public readonly BigDecimal $baseProverVolume,
        public readonly BigDecimal $maxRunVolume,
        public readonly BigDecimal $minRunVolume,
        public readonly BigDecimal $repeatabilityPercent,
        public readonly bool $isConforme
    ) {}
}
