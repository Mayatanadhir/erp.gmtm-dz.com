<?php

declare(strict_types=1);

namespace App\Services;

final class ChromatographMetrologyService
{
    /**
     * قائمة المركبات الغازية القياسية الـ 11 المعتمدة لتحليل الغاز الطبيعي
     *
     * @return array<int, array{name: string, symbol: string, default_order: int}>
     */
    public function getDefaultComponents(): array
    {
        return [
            ['name' => 'C6+', 'symbol' => 'C6+', 'default_order' => 1],
            ['name' => 'Propane', 'symbol' => 'Propane', 'default_order' => 2],
            ['name' => 'i-Butane', 'symbol' => 'i-Butane', 'default_order' => 3],
            ['name' => 'n-Butane', 'symbol' => 'n-Butane', 'default_order' => 4],
            ['name' => 'Neopentane', 'symbol' => 'Neopentane', 'default_order' => 5],
            ['name' => 'i-Pentane', 'symbol' => 'i-Pentane', 'default_order' => 6],
            ['name' => 'n-Pentane', 'symbol' => 'n-Pentane', 'default_order' => 7],
            ['name' => 'Nitrogen', 'symbol' => 'Nitrogen', 'default_order' => 8],
            ['name' => 'Methane', 'symbol' => 'Methane', 'default_order' => 9],
            ['name' => 'Carbon Dioxide', 'symbol' => 'Carbon Dioxide', 'default_order' => 10],
            ['name' => 'Ethane', 'symbol' => 'Ethane', 'default_order' => 11],
            ['name' => 'Helium', 'symbol' => 'Helium', 'default_order' => 12],
        ];
    }

    /**
     * قائمة الخصائص الفيزيائية والطاقوية المعتمدة وفق OIML R 140 / ISO 6976
     *
     * @return array<int, array{name: string, symbol: string, unit: string, emt_limit: float, has_repeatability: bool, repeatability_limit: float|null}>
     */
    public function getDefaultPhysicalProperties(): array
    {
        return [
            [
                'name' => 'Gross Calorific Value (PCS)',
                'symbol' => 'PCS',
                'unit' => 'MJ/m3',
                'emt_limit' => 0.50,
                'has_repeatability' => true,
                'repeatability_limit' => 0.10,
            ],
            [
                'name' => 'Net Calorific Value (PCI)',
                'symbol' => 'PCI',
                'unit' => 'MJ/m3',
                'emt_limit' => 0.50,
                'has_repeatability' => false,
                'repeatability_limit' => null,
            ],
            [
                'name' => 'Masse Volumique (Density)',
                'symbol' => 'Pb',
                'unit' => 'kg/m3',
                'emt_limit' => 0.35,
                'has_repeatability' => false,
                'repeatability_limit' => null,
            ],
            [
                'name' => 'Compressibility Factor (Z)',
                'symbol' => 'Zb',
                'unit' => 'sans dimension',
                'emt_limit' => 0.30,
                'has_repeatability' => false,
                'repeatability_limit' => null,
            ],
        ];
    }

    /**
     * حساب تكرارية أداة القياس الفعلية لمجموعة قراءات وفق معيار ASTM D 1945-14
     * الفارق الأقصى بين عمليتي حقن متتاليتين Max(|Run_i - Run_{i+1}|)
     *
     * @param  array<int, float>  $runs
     */
    public function calculateRepeatability(array $runs): float
    {
        $filteredRuns = array_values(array_filter(array_map('floatval', $runs), fn ($v) => ! is_nan($v)));
        $count = count($filteredRuns);

        if ($count < 2) {
            return 0.0;
        }

        $diffs = [];
        for ($i = 0; $i < $count - 1; $i++) {
            $diffs[] = abs($filteredRuns[$i] - $filteredRuns[$i + 1]);
        }

        return (float) round(max($diffs), 3);
    }

    /**
     * تحديد حد التكرارية الأقصى المسموح به وفق معيار ASTM D 1945-14
     * استناداً إلى متوسط نسبة المركب (% mol/mol)
     */
    public function getAstmRepeatabilityLimit(float $meanConcentration): float
    {
        $mean = abs($meanConcentration);

        return match (true) {
            $mean <= 0.09 => 0.010,
            $mean <= 0.90 => 0.040,
            $mean <= 4.90 => 0.070,
            $mean <= 10.00 => 0.080,
            default => 0.100,
        };
    }

    /**
     * حساب الخطأ النسبي لدقة القياس لمركبات الغاز وفق ISO 6974-2 وتقارير المعايرة الرسمية
     * الصيغة المعتمدة لمركبات الغاز: (RawMean - Reference) / Reference
     * بدقة 3 أرقام بعد الفاصلة.
     * مثال C6+: المتوسط الفعلي للحقنات = 0.10616 والمرجع = 0.1084:
     * ((0.10616 - 0.1084) / 0.1084) = -0.02066... ≈ -0.021 (-0.021 %)
     */
    public function calculateRelativeError(float $mean, float $reference): float
    {
        if (abs($reference) < 1e-7) {
            return 0.0;
        }

        return (float) round(($mean - $reference) / $reference, 3);
    }

    /**
     * تحديد حد خطأ الدقة الأقصى المسموح به (EMT %) وفق المواصفة القياسية ISO 6974-2
     * استناداً إلى القيمة المرجعية للمركب في أسطوانة الغاز (% mol/mol)
     */
    public function getIsoEmtLimit(float $reference): float
    {
        $ref = abs($reference);

        return match (true) {
            $ref < 0.10 => 100.0,
            $ref < 1.00 => 50.0,
            $ref < 10.00 => 10.0,
            $ref < 50.00 => 5.0,
            default => 3.0,
        };
    }

    /**
     * معالجة ومطابقة مركب فردي من الكسور المولية
     *
     * @param  array<int, float>  $runs  القراءات الخمس
     * @param  float  $reference  القيمة المرجعية للأسطوانة
     * @return array{mean_value: float, repeatability: float, repeatability_limit_astm: float, repeatability_is_conforme: bool, relative_error_percent: float, emt_limit_percent: float, error_is_conforme: bool, is_conforme: bool}
     */
    public function evaluateComponent(array $runs, float $reference): array
    {
        $filteredRuns = array_map('floatval', $runs);
        $count = count($filteredRuns);
        $rawMean = $count > 0 ? (array_sum($filteredRuns) / $count) : 0.0;
        $mean = (float) round($rawMean, 4);

        $repeatability = $this->calculateRepeatability($filteredRuns);
        $astmLimit = $this->getAstmRepeatabilityLimit($mean);
        $repeatabilityConforme = $repeatability <= ($astmLimit + 1e-6);

        $relError = $this->calculateRelativeError($rawMean, $reference);
        $isoLimit = $this->getIsoEmtLimit($reference);
        $percentError = abs(($rawMean - $reference) / ($reference ?: 1.0)) * 100.0;
        $errorConforme = $percentError <= ($isoLimit + 1e-6);

        return [
            'mean_value' => $mean,
            'repeatability' => $repeatability,
            'repeatability_limit_astm' => $astmLimit,
            'repeatability_is_conforme' => $repeatabilityConforme,
            'relative_error_percent' => $relError,
            'emt_limit_percent' => $isoLimit,
            'error_is_conforme' => $errorConforme,
            'is_conforme' => ($repeatabilityConforme && $errorConforme),
        ];
    }

    /**
     * معالجة ومطابقة خاصية فيزيائية أو طاقوية وفق OIML R 140 الفئة A
     *
     * @param  array<int, float>  $runs  قراءات الخاصية في الدورات الخمس
     * @param  float  $reference  القيمة المرجعية في شهادة الغاز
     * @param  float  $emtLimit  حد خطأ الدقة (مثل 0.50% للـ PCS)
     * @param  float|null  $repeatabilityLimit  حد التكرارية (0.10% للـ PCS)
     * @return array{mean_value: float, relative_error_percent: float, emt_limit_percent: float, error_is_conforme: bool, repeatability: float|null, repeatability_limit: float|null, repeatability_is_conforme: bool, is_conforme: bool}
     */
    public function evaluatePhysicalProperty(
        array $runs,
        float $reference,
        float $emtLimit,
        ?float $repeatabilityLimit = null
    ): array {
        $filteredRuns = array_map('floatval', $runs);
        $count = count($filteredRuns);
        $rawMean = $count > 0 ? (array_sum($filteredRuns) / $count) : 0.0;
        $mean = (float) round($rawMean, 13);

        $relError = (abs($reference) > 1e-7)
            ? (float) round((($rawMean - $reference) / $reference) * 100.0, 2)
            : 0.0;
        $errorConforme = abs($relError) <= ($emtLimit + 1e-6);

        $repeatability = $this->calculateRepeatability($filteredRuns);
        $hasRepLimit = $repeatabilityLimit !== null;
        $repeatabilityConforme = $hasRepLimit ? ($repeatability <= ($repeatabilityLimit + 1e-6)) : true;

        return [
            'mean_value' => $mean,
            'relative_error_percent' => $relError,
            'emt_limit_percent' => $emtLimit,
            'error_is_conforme' => $errorConforme,
            'repeatability' => $repeatability,
            'repeatability_limit' => $repeatabilityLimit,
            'repeatability_is_conforme' => $repeatabilityConforme,
            'is_conforme' => ($errorConforme && $repeatabilityConforme),
        ];
    }

    /**
     * مطابقة وتجميع مركبات الكروماتوغراف الـ 11 مع مركبات AGA8 الـ 21.
     *
     * @param  array<string, float|int|string|null>  $components
     * @return array<string, float>
     */
    public function mapComponentsToAga8(array $components): array
    {
        $aga8Map = [
            'methane' => 'Methane',
            'c1' => 'Methane',
            'nitrogen' => 'Nitrogen',
            'n2' => 'Nitrogen',
            'carbon dioxide' => 'Carbon dioxide',
            'carbon_dioxide' => 'Carbon dioxide',
            'co2' => 'Carbon dioxide',
            'ethane' => 'Ethane',
            'c2' => 'Ethane',
            'propane' => 'Propane',
            'c3' => 'Propane',
            'i-butane' => 'Isobutane',
            'i_butane' => 'Isobutane',
            'ic4' => 'Isobutane',
            'isobutane' => 'Isobutane',
            'n-butane' => 'n-Butane',
            'n_butane' => 'n-Butane',
            'nc4' => 'n-Butane',
            'butane' => 'n-Butane',
            'i-pentane' => 'Isopentane',
            'i_pentane' => 'Isopentane',
            'ic5' => 'Isopentane',
            'isopentane' => 'Isopentane',
            'neopentane' => 'Neopentane',
            'neoc5' => 'Neopentane',
            'n-pentane' => 'n-Pentane',
            'n_pentane' => 'n-Pentane',
            'nc5' => 'n-Pentane',
            'pentane' => 'n-Pentane',
            'c6+' => 'Hexane',
            'c6_plus' => 'Hexane',
            'c6' => 'Hexane',
            'hexane' => 'Hexane',
            'helium' => 'Helium',
            'he' => 'Helium',
        ];

        $composition = [];
        foreach ($components as $key => $val) {
            if ($val === null || $val === '') {
                continue;
            }
            $cleanKey = mb_strtolower(trim((string) $key));
            $target = $aga8Map[$cleanKey] ?? null;
            if ($target) {
                $composition[$target] = ($composition[$target] ?? 0.0) + (float) $val;
            }
        }

        return $composition;
    }

    /**
     * حساب الخصائص الفيزيائية والطاقوية للكروماتوغراف (PCS, PCI, Zb, Pb)
     * وفق معيار الطاقة ISO 6976:1995 ومحرك AGA8-Detail عند الشروط القياسية (15 °C و 101.325 kPa)
     *
     * @param  array<string, float|int|string|null>  $components  الكسور أو النسب المئوية للمركبات
     * @param  float  $temperatureC  درجة الحرارة بالمئوي (°C) - الشروط القياسية 15.0 °C
     * @param  float  $pressureKpa  الضغط بالكيلوباسكال (kPa) - الشروط القياسية 101.325 kPa
     * @return array{PCS: float|null, PCI: float|null, Zb: float, Pb: float, molar_mass: float, molar_density_mol_l: float, speed_of_sound_m_s: float, heat_capacity_cp: float, heat_capacity_cv: float, isentropic_exponent: float, energy_iso6976: array<string, mixed>|null, thermo_aga8: array<string, mixed>|null, raw: array<string, mixed>}|null
     */
    public function calculateAga8Properties(
        array $components,
        float $temperatureC = 15.0,
        float $pressureKpa = 101.325
    ): ?array {
        $aga8Composition = $this->mapComponentsToAga8($components);
        if (empty($aga8Composition) || array_sum($aga8Composition) <= 0) {
            return null;
        }

        /** @var Aga8Service $aga8 */
        $aga8 = app(Aga8Service::class);

        try {
            $results = $aga8->calculateWithUnits(
                pressure: $pressureKpa,
                pressureUnit: 'kpa',
                temperature: $temperatureC,
                tempUnit: 'c',
                composition: $aga8Composition
            );

            // استخراج خصائص ISO 6976 (PCS, PCI, Zb, Pb)
            $pcs = isset($results['gross_calorific_value_pcs_mj_m3'])
                ? (float) $results['gross_calorific_value_pcs_mj_m3']
                : null;
            $pci = isset($results['net_calorific_value_pci_mj_m3'])
                ? (float) $results['net_calorific_value_pci_mj_m3']
                : null;

            // استخدام Zb و Pb القياسيين المعتمدين من ISO 6976 أو AGA8
            $zb = isset($results['compressibility_factor_zb'])
                ? (float) $results['compressibility_factor_zb']
                : (float) $results['compressibility_factor_z'];

            $pb = isset($results['density_pb_kg_m3'])
                ? (float) $results['density_pb_kg_m3']
                : (float) ((float) $results['molar_density_mol_l'] * (float) $results['molar_mass_g_mol']);

            $molarDensity = (float) $results['molar_density_mol_l'];
            $molarMass = (float) $results['molar_mass_g_mol'];

            return [
                'PCS' => $pcs !== null ? round($pcs, 13) : null,
                'PCI' => $pci !== null ? round($pci, 13) : null,
                'Zb' => round($zb, 13),
                'Pb' => round($pb, 13),
                'molar_mass' => round($molarMass, 13),
                'molar_density_mol_l' => round($molarDensity, 13),
                'speed_of_sound_m_s' => round((float) $results['speed_of_sound_m_s'], 2),
                'heat_capacity_cp' => round((float) $results['heat_capacity_cp_j_mol_k'], 3),
                'heat_capacity_cv' => round((float) $results['heat_capacity_cv_j_mol_k'], 3),
                'isentropic_exponent' => round((float) $results['isentropic_exponent'], 4),
                'energy_iso6976' => $results['energy_properties_iso6976'] ?? null,
                'thermo_aga8' => $results['thermodynamic_properties_aga8'] ?? null,
                'raw' => $results,
            ];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
