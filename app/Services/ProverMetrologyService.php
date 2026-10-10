<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\MetrologyConstants;
use App\DTOs\Metrology\CalculatedRunDTO;
use App\DTOs\Metrology\MetrologyCalculationResultDTO;
use App\DTOs\Metrology\ProverRunDTO;
use App\DTOs\Metrology\ProverTubeDTO;
use App\DTOs\Metrology\ProverVerificationDTO;
use App\DTOs\Metrology\StandardGaugeDTO;
use App\Exceptions\MetrologyCalculationException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Throwable;

final class ProverMetrologyService
{
    /**
     * Calculate pure water density rho(T) according to ISO 8222 / Tanaka et al.
     * Formula: rho(t) = A5 * [ 1 - ((t + A1)^2 * (t + A2)) / (A3 * (t + A4)) ]
     */
    public function calculateWaterDensity(BigDecimal $temperature): BigDecimal
    {
        try {
            $t = $temperature;
            $a1 = BigDecimal::of(MetrologyConstants::WATER_DENS_A1);
            $a2 = BigDecimal::of(MetrologyConstants::WATER_DENS_A2);
            $a3 = BigDecimal::of(MetrologyConstants::WATER_DENS_A3);
            $a4 = BigDecimal::of(MetrologyConstants::WATER_DENS_A4);
            $a5 = BigDecimal::of(MetrologyConstants::WATER_DENS_A5);

            // (t + A1)^2
            $tPlusA1 = $t->plus($a1);
            $term1 = $tPlusA1->multipliedBy($tPlusA1);

            // (t + A2)
            $term2 = $t->plus($a2);

            // Numerator: (t + A1)^2 * (t + A2)
            $numerator = $term1->multipliedBy($term2);

            // Denominator: A3 * (t + A4)
            $denominator = $a3->multipliedBy($t->plus($a4));

            if ($denominator->isZero()) {
                throw new MetrologyCalculationException('Water density denominator is zero.');
            }

            // Fraction
            $fraction = $numerator->dividedBy($denominator, MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp);

            // [ 1 - fraction ]
            $bracket = BigDecimal::of('1')->minus($fraction);

            // Final density
            return $a5->multipliedBy($bracket)->toScale(MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp);
        } catch (Throwable $e) {
            throw new MetrologyCalculationException('Failed to calculate water density: '.$e->getMessage(), 422, $e);
        }
    }

    /**
     * Calculate water density correction factor (C_tdw) according to ISO 8222
     * C_tdw = rho(T_tm) / rho(T_p)
     */
    public function calculateCtdw(BigDecimal $gaugeTemp, BigDecimal $proverTemp): BigDecimal
    {
        $rhoMeasure = $this->calculateWaterDensity($gaugeTemp);
        $rhoProver = $this->calculateWaterDensity($proverTemp);

        if ($rhoProver->isZero()) {
            throw new MetrologyCalculationException('Prover water density is zero in Ctdw calculation.');
        }

        return $rhoMeasure->dividedBy($rhoProver, MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate test measure thermal expansion factor (C_tsm) according to ISO 4267-2
     * C_tsm = 1 + (T_tm - T_b) * G_cm
     */
    public function calculateCtsm(BigDecimal $gaugeTemp, BigDecimal $refTemp, BigDecimal $gcm): BigDecimal
    {
        $deltaT = $gaugeTemp->minus($refTemp);
        $product = $deltaT->multipliedBy($gcm);

        return BigDecimal::of('1')->plus($product)->toScale(MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate pipe prover thermal expansion factor (C_tsp) according to ISO 4267-2
     * C_tsp = 1 + (T_p - T_b) * G_c
     */
    public function calculateCtsp(BigDecimal $proverTemp, BigDecimal $refTemp, BigDecimal $gc): BigDecimal
    {
        $deltaT = $proverTemp->minus($refTemp);
        $product = $deltaT->multipliedBy($gc);

        return BigDecimal::of('1')->plus($product)->toScale(MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate dual thermal expansion factor for Small Volume Prover (Compact SVP)
     * according to OIML R 119 / API MPMS 12.2.4
     * C_tsp = [1 + (T_p - T_b) * G_a] * [1 + (T_shaft - T_b) * G_l]
     */
    public function calculateCtspSvp(
        BigDecimal $proverTemp,
        BigDecimal $shaftTemp,
        BigDecimal $refTemp,
        BigDecimal $ga,
        BigDecimal $gl
    ): BigDecimal {
        $deltaTp = $proverTemp->minus($refTemp);
        $deltaTd = $shaftTemp->minus($refTemp);

        $chamberTerm = BigDecimal::of('1')->plus($deltaTp->multipliedBy($ga));
        $shaftTerm = BigDecimal::of('1')->plus($deltaTd->multipliedBy($gl));

        return $chamberTerm->multipliedBy($shaftTerm)->toScale(MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate prover wall pressure elasticity factor (C_psp) according to ISO 4267-2
     * C_psp = 1 + (P_p * ID) / (E * WT)
     */
    public function calculateCpsp(BigDecimal $pressure, BigDecimal $diameter, BigDecimal $thickness, BigDecimal $elasticityModulus): BigDecimal
    {
        if ($pressure->isZero()) {
            return BigDecimal::of('1.00000000');
        }

        $numerator = $pressure->multipliedBy($diameter);
        $denominator = $elasticityModulus->multipliedBy($thickness);

        if ($denominator->isZero()) {
            throw new MetrologyCalculationException('Prover mechanical denominator (E * WT) is zero.');
        }

        $fraction = $numerator->dividedBy($denominator, MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp);

        return BigDecimal::of('1')->plus($fraction)->toScale(MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate water compressibility factor (C_plp) in prover according to API MPMS 12.2.4 / OIML R 119
     * C_plp = 1 / (1 - P_bar * F)
     * where F = 0.0000464 bar^-1
     */
    public function calculateCplp(BigDecimal $pressureBar, ?BigDecimal $proverTemp = null): BigDecimal
    {
        if ($pressureBar->isZero()) {
            return BigDecimal::of('1.00000000');
        }

        $fp = BigDecimal::of(MetrologyConstants::WATER_COMPRESSIBILITY_BAR);
        $pf = $pressureBar->multipliedBy($fp);
        $denominator = BigDecimal::of('1')->minus($pf);

        if ($denominator->isZero()) {
            throw new MetrologyCalculationException('Denominator (1 - P * F) in C_plp is zero.');
        }

        return BigDecimal::of('1')->dividedBy($denominator, MetrologyConstants::DEFAULT_FACTOR_SCALE, RoundingMode::HalfUp);
    }

    /**
     * Calculate correction factors and volume for a single run / filling
     */
    public function calculateRun(
        ProverRunDTO $run,
        ProverTubeDTO $prover,
        StandardGaugeDTO $gauge,
        BigDecimal $refTemp,
        string $pressureUnit = 'bar'
    ): CalculatedRunDTO {
        $indicatedVolume = $run->indicatedVolume ?? $gauge->nominalVolume;

        // Pressure unit normalization: kPa to bar
        $pressureRaw = $run->proverPressure;
        $isKpa = strtolower($pressureUnit) === 'kpa';
        $pressureBar = $isKpa
            ? $pressureRaw->dividedBy(BigDecimal::of('100'), MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp)
            : $pressureRaw;

        // Correction factors
        $cTdw = $this->calculateCtdw($run->gaugeTemperature, $run->proverTemperature);
        $cTsm = $this->calculateCtsm($run->gaugeTemperature, $refTemp, $gauge->thermalExpansion);

        if ($prover->type === 'compact_svp' && $run->shaftTemperature !== null) {
            $ga = $prover->areaExpansion ?? BigDecimal::of(MetrologyConstants::G_A_SVP_CYLINDER);
            $gl = $prover->linearExpansion ?? BigDecimal::of(MetrologyConstants::G_L_SVP_SHAFT);
            $cTsp = $this->calculateCtspSvp($run->proverTemperature, $run->shaftTemperature, $refTemp, $ga, $gl);
        } else {
            $cTsp = $this->calculateCtsp($run->proverTemperature, $refTemp, $prover->thermalExpansion);
        }

        $cPsp = $this->calculateCpsp($pressureBar, $prover->diameter, $prover->thickness, $prover->elasticityModulus);
        $cPlp = $this->calculateCplp($pressureBar, $run->proverTemperature);

        // Common thermal ratio: CCTS = C_tsm / C_tsp
        $ccts = $cTsm->dividedBy($cTsp, MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp);

        // Pressure divisor: CPS * CPL
        $pressureDivisor = $cPsp->multipliedBy($cPlp);
        if ($pressureDivisor->isZero()) {
            throw new MetrologyCalculationException('Pressure divisor (C_psp * C_plp) is zero.');
        }

        // Corrected volume: V_b = V_ind * CCTS * CTDW / (C_psp * C_plp)
        $numerator = $indicatedVolume->multipliedBy($ccts)->multipliedBy($cTdw);
        $correctedVolume = $numerator->dividedBy($pressureDivisor, MetrologyConstants::DEFAULT_VOLUME_SCALE, RoundingMode::HalfUp);

        return new CalculatedRunDTO(
            runNumber: $run->runNumber,
            fillNumber: $run->fillNumber,
            indicatedVolume: $indicatedVolume,
            gaugeTemperature: $run->gaugeTemperature,
            proverTemperature: $run->proverTemperature,
            proverPressure: $run->proverPressure,
            cTdw: $cTdw,
            cTsm: $cTsm,
            cTsp: $cTsp,
            cPsp: $cPsp,
            cPlp: $cPlp,
            correctedVolume: $correctedVolume,
            scaleReadingMm: $run->scaleReadingMm,
            shaftTemperature: $run->shaftTemperature
        );
    }

    /**
     * Calculate complete prover verification session with repeatability and conformity evaluation
     */
    public function calculateVerification(ProverVerificationDTO $dto): MetrologyCalculationResultDTO
    {
        if (empty($dto->runs)) {
            throw new MetrologyCalculationException('At least 3 runs are required for prover verification.');
        }

        $calculatedRuns = [];
        $runsByNumber = [];

        foreach ($dto->runs as $run) {
            $calculatedRun = $this->calculateRun(
                run: $run,
                prover: $dto->proverTube,
                gauge: $dto->standardGauge,
                refTemp: $dto->referenceTemperature,
                pressureUnit: $dto->pressureUnit
            );

            $calculatedRuns[] = $calculatedRun;
            $runsByNumber[$run->runNumber][] = $calculatedRun;
        }

        if (count($runsByNumber) < 3) {
            throw new MetrologyCalculationException('A minimum of 3 consecutive runs is required by OAM / API MPMS.');
        }

        // Aggregate volume per run (sum of partial fillings)
        $runAggregatedVolumes = [];
        foreach ($runsByNumber as $runNum => $fills) {
            $sum = BigDecimal::zero();
            foreach ($fills as $fill) {
                $sum = $sum->plus($fill->correctedVolume);
            }
            $runAggregatedVolumes[$runNum] = $sum->toScale(MetrologyConstants::DEFAULT_VOLUME_SCALE, RoundingMode::HalfUp);
        }

        // Find max and min run volumes
        $maxRunVolume = null;
        $minRunVolume = null;
        $totalVolumeSum = BigDecimal::zero();

        foreach ($runAggregatedVolumes as $volume) {
            if ($maxRunVolume === null || $volume->compareTo($maxRunVolume) > 0) {
                $maxRunVolume = $volume;
            }
            if ($minRunVolume === null || $volume->compareTo($minRunVolume) < 0) {
                $minRunVolume = $volume;
            }
            $totalVolumeSum = $totalVolumeSum->plus($volume);
        }

        if ($minRunVolume === null || $minRunVolume->isZero()) {
            throw new MetrologyCalculationException('Minimum run volume is zero or undefined.');
        }

        // Base Prover Volume: BPV = sum / runs_count
        $runsCount = BigDecimal::of((string) count($runAggregatedVolumes));
        $baseProverVolume = $totalVolumeSum->dividedBy($runsCount, MetrologyConstants::DEFAULT_VOLUME_SCALE, RoundingMode::HalfUp);

        // Repeatability: r(%) = ((CPV_max - CPV_min) / CPV_min) * 100
        $spread = $maxRunVolume->minus($minRunVolume);
        $ratio = $spread->dividedBy($minRunVolume, MetrologyConstants::INTERNAL_CALCULATION_SCALE, RoundingMode::HalfUp);
        $repeatabilityPercent = $ratio->multipliedBy(BigDecimal::of('100'))->toScale(MetrologyConstants::DEFAULT_PERCENT_SCALE, RoundingMode::HalfUp);

        // Legal compliance check: r <= 0.020%
        $maxAllowed = BigDecimal::of(MetrologyConstants::MAX_REPEATABILITY_PERCENT);
        $isConforme = $repeatabilityPercent->compareTo($maxAllowed) <= 0;

        return new MetrologyCalculationResultDTO(
            calculatedRuns: $calculatedRuns,
            runAggregatedVolumes: $runAggregatedVolumes,
            baseProverVolume: $baseProverVolume,
            maxRunVolume: $maxRunVolume,
            minRunVolume: $minRunVolume,
            repeatabilityPercent: $repeatabilityPercent,
            isConforme: $isConforme
        );
    }
}
