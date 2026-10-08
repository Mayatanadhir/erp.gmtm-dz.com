<?php

declare(strict_types=1);

namespace App\Services;

class OamMetrologyService
{
    // =========================================================================
    // ثوابت المترولوجيا (Constants) - حدود الخطأ الأقصى المسموح به (EMT)
    // =========================================================================

    public const EMT_PT100_BASE = 0.15;

    public const EMT_PT100_MULT = 0.002;

    public const EMT_GAS_TEMP_ABS = 0.5;

    public const EMT_GAS_PRESS_REL = 0.2;

    public const EMT_LIQ_TEMP_TRAD = 0.24;

    public const EMT_LIQ_TEMP_SMART = 0.3;

    public const EMT_LIQ_PRESS_SPAN_10_TRAD = 0.4;

    public const EMT_LIQ_PRESS_SPAN_10_SMART = 0.5;

    public const EMT_LIQ_PRESS_SPAN_40_TRAD = 4.0;

    public const EMT_LIQ_PRESS_SPAN_40_SMART = 5.0;

    public const EMT_LIQ_PRESS_SPAN_MAX_TRAD = 1.6;

    public const EMT_LIQ_PRESS_SPAN_MAX_SMART = 2.0;

    public const EMT_ADC_GAS_TEMP_ABS = 0.1;

    public const EMT_ADC_GAS_PRESS_REL = 0.05;

    public const EMT_ADC_LIQ_TEMP_ABS = 0.18;

    public const EMT_ADC_LIQ_PRESS_SPAN_10 = 0.3;

    public const EMT_ADC_LIQ_PRESS_SPAN_40 = 3.0;

    public const EMT_ADC_LIQ_PRESS_SPAN_MAX = 1.2;

    // =========================================================================
    // الدوال الحسابية (Methods)
    // =========================================================================

    /**
     * حساب المقاومة الكهربائية R(T) لمستشعر PT100 انطلاقاً من درجة الحرارة (معيار IEC 60751)
     */
    public function temperatureToResistance(float $t): float
    {
        $r0 = 100.0;
        $a = 3.9083e-3;
        $b = -5.775e-7;
        $c = -4.183e-12;

        if ($t >= 0) {
            return $r0 * (1 + $a * $t + $b * $t * $t);
        }

        return $r0 * (1 + $a * $t + $b * $t * $t + $c * ($t - 100) * pow($t, 3));
    }

    /**
     * حساب درجة الحرارة T(R) لمستشعر PT100 انطلاقاً من المقاومة الكهربائية (معيار IEC 60751)
     */
    public function resistanceToTemperature(float $r): float
    {
        $r0 = 100.0;
        $a = 3.9083e-3;
        $b = -5.775e-7;
        $c = -4.183e-12;

        if ($r >= $r0) {
            $discriminant = pow($r0 * $a, 2) - 4 * ($r0 * $b) * ($r0 - $r);
            if ($discriminant < 0) {
                return NAN;
            }

            return (-$r0 * $a + sqrt($discriminant)) / (2 * $r0 * $b);
        }

        // خوارزمية نيوتن-رافسون (Newton-Raphson) للنطاق السالب R < 100
        $t = -100.0;
        for ($i = 0; $i < 20; $i++) {
            $f = $r0 * (1 + $a * $t + $b * $t * $t + $c * ($t - 100) * pow($t, 3)) - $r;
            $fPrime = $r0 * ($a + 2 * $b * $t - 300 * $c * $t * $t + 4 * $c * pow($t, 3));
            if ($fPrime == 0) {
                break;
            }
            $tNext = $t - $f / $fPrime;

            if (abs($tNext - $t) < 1e-5) {
                return $tNext;
            }
            $t = $tNext;
        }

        return $t;
    }

    /**
     * تقييم مسبار الحرارة Pt100 وحساب الخطأ وحدود EMT والمطابقة
     */
    public function evaluateProbePt100(float $appliedTemp, float $indicatedTemp): array
    {
        $emt = self::EMT_PT100_BASE + (self::EMT_PT100_MULT * abs($appliedTemp));
        $absoluteError = $indicatedTemp - $appliedTemp;

        return [
            'error' => $absoluteError,
            'error_type' => 'Absolute',
            'emt' => $emt,
            'is_conforme' => abs($absoluteError) <= $emt,
        ];
    }

    /**
     * تقييم مرسل الإشارة وحساب الأخطاء وفق معايير OIML
     */
    public function evaluateTransmitter(
        float $span,
        float $min,
        float $refValue,
        ?float $mA,
        ?float $indicatedValue,
        string|\BackedEnum|null $fluid = 'Liquid',
        ?string $tech = 'Traditional',
        ?string $measurand = 'Pression',
        ?string $pressureType = 'Relative',
        ?float $ambientPressure = 0.0
    ): array {
        // حماية البيانات (Null Safety & Defaults)
        if ($fluid instanceof \BackedEnum) {
            $fluid = ucfirst(strtolower((string) $fluid->value));
        } elseif (is_string($fluid)) {
            $fluid = ucfirst(strtolower($fluid));
        }
        $fluid = in_array($fluid, ['Gas', 'Gaz']) ? 'Gas' : 'Liquid';
        $tech = $tech ?: 'Traditional';
        $measurand = $measurand ?: 'Pression';
        $pressureType = $pressureType ?: 'Relative';
        $ambientPressure = (float) $ambientPressure;
        $isTemp = stripos($measurand, 'Temp') !== false;

        // التصحيح الفيزيائي للضغط المرجعي في حالة الضغط المطلق
        if (! $isTemp && $pressureType === 'Absolute') {
            $refValue += $ambientPressure;
        }

        // تحديد القيمة المقاسة بناءً على التكنولوجيا والمائع
        if (($fluid === 'Gas' || $tech === 'SMART') && $indicatedValue !== null && $indicatedValue !== '') {
            $measuredPhysicalValue = (float) $indicatedValue;
        } else {
            $mA = (float) $mA;
            $measuredPhysicalValue = (($mA - 4) / 16) * $span + $min;
        }

        // الحسابات الأساسية للخطأ
        $absoluteError = $measuredPhysicalValue - $refValue;
        $relativeError = ($span != 0) ? ($absoluteError / $span) * 100 : 0;

        // تطبيق قوانين OIML
        if ($fluid === 'Gas') {
            if ($isTemp) {
                return $this->formatResult($absoluteError, 'Absolute', self::EMT_GAS_TEMP_ABS);
            } else {
                return $this->formatResult($relativeError, 'Relative', self::EMT_GAS_PRESS_REL);
            }
        }

        if ($isTemp) {
            $emt = ($tech === 'SMART') ? self::EMT_LIQ_TEMP_SMART : self::EMT_LIQ_TEMP_TRAD;

            return $this->formatResult($absoluteError, 'Absolute', $emt);
        } else {
            if ($span <= 10) {
                $emt = ($tech === 'SMART') ? self::EMT_LIQ_PRESS_SPAN_10_SMART : self::EMT_LIQ_PRESS_SPAN_10_TRAD;

                return $this->formatResult($absoluteError, 'Absolute', $emt);
            } elseif ($span <= 40) {
                $emt = ($tech === 'SMART') ? self::EMT_LIQ_PRESS_SPAN_40_SMART : self::EMT_LIQ_PRESS_SPAN_40_TRAD;

                return $this->formatResult($relativeError, 'Relative', $emt);
            } else {
                $emt = ($tech === 'SMART') ? self::EMT_LIQ_PRESS_SPAN_MAX_SMART : self::EMT_LIQ_PRESS_SPAN_MAX_TRAD;

                return $this->formatResult($absoluteError, 'Absolute', $emt);
            }
        }
    }

    /**
     * تقييم قنوات التحويل التناظري/الرقمي وحاسبات التدفق ADC / Flow Computer
     */
    public function evaluateADC(
        float $span,
        float $min,
        ?float $vMes,
        ?float $rStandard,
        ?float $indicatedValue,
        string|\BackedEnum|null $fluid = 'Liquid',
        ?string $measurand = 'Pression'
    ): array {
        if ($fluid instanceof \BackedEnum) {
            $fluid = ucfirst(strtolower((string) $fluid->value));
        } elseif (is_string($fluid)) {
            $fluid = ucfirst(strtolower($fluid));
        }
        $fluid = in_array($fluid, ['Gas', 'Gaz']) ? 'Gas' : 'Liquid';
        $measurand = $measurand ?: 'Pression';
        $vMes = (float) $vMes;
        $rStandard = (float) $rStandard;
        $indicatedValue = (float) $indicatedValue;
        $isTemp = stripos($measurand, 'Temp') !== false;

        // إذا كانت الإشارة المدخلة بالتيار mA (4 to 20 mA)
        if ($vMes >= 2.0) {
            $equivValue = (($vMes - 4.0) / 16.0) * $span + $min;
        } else {
            // إذا كانت الإشارة مقاسة بالجهد عبر المقاومة V (1 to 5 V عبر 250 أوم، أو 0.2 to 1 V عبر 50 أوم)
            if ($rStandard == 250.0) {
                $equivValue = (($vMes - 1.0) / 4.0) * $span + $min;
            } else {
                $equivValue = (($vMes - 0.2) / 0.8) * $span + $min;
            }
        }

        $absoluteError = $indicatedValue - $equivValue;
        $relativeError = ($span != 0) ? ($absoluteError / $span) * 100 : 0;

        if ($fluid === 'Gas') {
            if ($isTemp) {
                return $this->formatResult($absoluteError, 'Absolute', self::EMT_ADC_GAS_TEMP_ABS, $equivValue);
            } else {
                return $this->formatResult($relativeError, 'Relative', self::EMT_ADC_GAS_PRESS_REL, $equivValue);
            }
        } else {
            if ($isTemp) {
                return $this->formatResult($absoluteError, 'Absolute', self::EMT_ADC_LIQ_TEMP_ABS, $equivValue);
            } else {
                if ($span <= 10) {
                    return $this->formatResult($absoluteError, 'Absolute', self::EMT_ADC_LIQ_PRESS_SPAN_10, $equivValue);
                } elseif ($span <= 40) {
                    return $this->formatResult($relativeError, 'Relative', self::EMT_ADC_LIQ_PRESS_SPAN_40, $equivValue);
                } else {
                    return $this->formatResult($absoluteError, 'Absolute', self::EMT_ADC_LIQ_PRESS_SPAN_MAX, $equivValue);
                }
            }
        }
    }

    private function formatResult(float $errorValue, string $errorType, float $emt, ?float $equivValue = null): array
    {
        $result = [
            'error' => $errorValue,
            'error_type' => $errorType,
            'emt' => $emt,
            'is_conforme' => abs($errorValue) <= $emt,
        ];

        if ($equivValue !== null) {
            $result['equiv'] = $equivValue;
        }

        return $result;
    }
}
