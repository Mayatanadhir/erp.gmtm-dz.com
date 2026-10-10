<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Equipment;
use App\Models\FlowComputerVerification;
use App\Models\Grandeur;
use App\Models\Instrument;
use App\Models\ProbeVerification;
use App\Models\Report;
use App\Models\TransmitterVerification;
use Illuminate\Support\Collection;

final class CalibratorResolutionService
{
    /**
     * جلب جميع الأجهزة المعتمدة والمصحوبة بشهادات معايرة (requires_calibration = 1)
     * التابعة للمهمة الحالية فقط (الأجهزة المشاركة في المهمة حصراً)
     */
    public function getCertifiedMissionEquipments(Report $report): Collection
    {
        $mission = $report->mission;

        if (! $mission) {
            return collect();
        }

        return $mission->equipments()
            ->wherePivotNull('deleted_at')
            ->whereNotIn('equipment.category', ['vehicle', 'Vehicle', 'work_tool'])
            ->where(function ($q) {
                $q->whereIn('equipment.requires_calibration', ['1', 1, true])
                    ->orWhere('equipment.category', 'measuring_instrument');
            })
            ->with(['specifications.grandeur', 'grandeurs', 'calibrationCertificates'])
            ->get();
    }

    /**
     * استخراج المقادير الفيزيائية (Grandeurs) المعرفة للجهاز من جدول الخصائص الفنية (equipment_specifications)
     *
     * @return Collection<int, Grandeur>
     */
    public function getEquipmentGrandeurs(Equipment $equip): Collection
    {
        if ($equip->relationLoaded('grandeurs') && $equip->grandeurs && $equip->grandeurs->isNotEmpty()) {
            return $equip->grandeurs;
        }

        if ($equip->relationLoaded('specifications') && $equip->specifications && $equip->specifications->isNotEmpty()) {
            $mapped = $equip->specifications->map(fn ($spec) => $spec->grandeur)->filter()->values();
            if ($mapped->isNotEmpty()) {
                return $mapped;
            }
        }

        $directGrandeurs = $equip->grandeurs()->get();
        if ($directGrandeurs->isNotEmpty()) {
            return $directGrandeurs;
        }

        return $equip->specifications()->with('grandeur')->get()->map(fn ($spec) => $spec->grandeur)->filter()->values();
    }

    /**
     * فلترة الأجهزة حسب العائلة المترولوجية المعتمدة بشهادة
     * يشترط امتلاك الجهاز للمقدار الفيزيائي في قائمة خصائصه الفنية (equipment_specifications)
     * ويحجب أي جهاز لا يمتلك المقدار المطلوب.
     *
     * @param  string  $family  'pressure' | 'dp_pressure' | 'electrical' | 'transmitter_temperature' | 'probe_thermal' | 'probe_resistance'
     */
    public function filterByFamily(Collection $allEquips, string $family): Collection
    {
        return $allEquips->filter(function ($equip) use ($family) {
            $text = ($equip->full_name ?? '').' '.($equip->designation ?? '').' '.($equip->internal_code ?? '').' '.($equip->category instanceof \BackedEnum ? $equip->category->value : ($equip->category ?? ''));

            // استبعاد أجهزة القياس المحيطية والمرسلات المطلوب معايرتها من قوائم المراجع
            $isAmbient = (bool) preg_match('/thermo-hygrom|hygrom[eè]tre|hygrom/iu', $text);
            $isTransmitter = (bool) preg_match('/transmetteur\s+de/iu', $text);
            if ($isAmbient || $isTransmitter) {
                return false;
            }

            // استخراج المقادير الفيزيائية من قائمة خصائص الجهاز
            $grandeurs = $this->getEquipmentGrandeurs($equip);

            // استبعاد وحجب أي جهاز لا يمتلك مقادير فيزيائية في قائمة خصائصه الفنية
            if ($grandeurs->isEmpty()) {
                return false;
            }

            return match ($family) {
                // 1. عائلة أجهزة معايرة وتوليد الضغط (Pressure References)
                'pressure' => (function () use ($grandeurs) {
                    return $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Press') !== false ||
                        stripos($g->symbol ?? '', 'bar') !== false ||
                        stripos($g->symbol ?? '', 'mbar') !== false
                    );
                })(),

                // 2. عائلة أجهزة الضغط التفاضلي (DP References)
                'dp_pressure' => (function () use ($grandeurs, $text) {
                    $hasPressure = $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Press') !== false ||
                        stripos($g->symbol ?? '', 'bar') !== false ||
                        stripos($g->symbol ?? '', 'mbar') !== false
                    );
                    if (! $hasPressure) {
                        return false;
                    }

                    $hasMbarOrDiff = $grandeurs->contains(fn ($g) => stripos($g->symbol ?? '', 'mbar') !== false ||
                        stripos($g->name ?? '', 'Diff') !== false
                    );
                    $isDpText = (bool) preg_match('/dp|diff|Δp|-1.*1|adt.*672dp/iu', $text);

                    return $hasMbarOrDiff || $isDpText;
                })(),

                // 3. عائلة أجهزة قياس وتوليد التيار 4-20 mA والملتيميتر (Electrical References)
                'electrical' => (function () use ($grandeurs) {
                    return $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Courant') !== false ||
                        stripos($g->symbol ?? '', 'mA') !== false
                    );
                })(),

                // 4. عائلة أجهزة ومولدات الحرارة لمرسلات الحرارة (Temperature Generators)
                'transmitter_temperature', 'temperature' => (function () use ($grandeurs) {
                    return $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Temp') !== false ||
                        stripos($g->symbol ?? '', '°c') !== false
                    );
                })(),

                // 5. عائلة المراجع الحرارية للمسابير (Cal. 1: Bain/Four/Chaine de temp)
                'probe_thermal' => (function () use ($grandeurs, $text) {
                    if (preg_match('/r[eé]sistance[^a-z]*[eé]talon|boite[^a-z]*decade|boîte[^a-z]*décade/iu', $text)) {
                        return false;
                    }

                    return $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Temp') !== false ||
                        stripos($g->symbol ?? '', '°c') !== false
                    );
                })(),

                // 6. عائلة قياس وصناديق المقاومة للمسابير (Cal. 2: Résistance/Ω/Décades)
                'probe_resistance' => (function () use ($grandeurs) {
                    return $grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Resist') !== false ||
                        stripos($g->name ?? '', 'Résist') !== false ||
                        stripos($g->symbol ?? '', 'ohm') !== false ||
                        stripos($g->symbol ?? '', 'Ω') !== false
                    );
                })(),

                default => false,
            };
        })->values();
    }

    /**
     * نظام القراءة والفلترة الصريح والمباشر:
     * - خيارات options1 و options2 تأتي حصراً من العائلة المعتمدة بشهادة
     * - استرجاع الأجهزة المحفوظة مباشرة عبر role=1 و role=2 بدون أي تخمين
     */
    public function resolveDecoupledCalibrators(
        Report $report,
        Instrument $instrument,
        $verification,
        string $family1,
        string $family2,
        ?Instrument $channelTransmitter = null
    ): array {
        $isNewVerification = is_null($verification) || ! $verification->exists;
        $reportDefaults = $report->getDefaultCalibratorsForInstrument($instrument, $channelTransmitter);

        // 1. قراءة الأجهزة المحفوظة مباشرة من قاعدة البيانات بناءً على الدور (role)
        $savedCal1Id = null;
        $savedCal2Id = null;

        if (! $isNewVerification && $verification) {
            $cal1Row = $verification->calibrator1()->first();
            $savedCal1Id = $cal1Row?->id;

            $cal2Row = $verification->calibrator2()->first();
            $savedCal2Id = $cal2Row?->id;
        }

        // 2. جلب وتصفية أجهزة المهمة الحاملة لشهادات المعايرة حسب العائلة (المشاركة في المهمة حصراً)
        $allEquips = $this->getCertifiedMissionEquipments($report);
        $options1 = $this->filterByFamily($allEquips, $family1);
        $options2 = $this->filterByFamily($allEquips, $family2);

        // 3. تحديد القيمة المحددة: old() -> المحفوظ في DB -> الافتراضي للتقرير
        // يشترط أن يكون الجهاز المختار من ضمن خيارات المهمة المتاحة المؤهلة (options1 / options2)
        $selectedCal1 = old(
            'calibrator_1',
            $savedCal1Id ?? ($isNewVerification ? ($reportDefaults[0] ?? null) : null)
        );
        $selectedCal2 = old(
            'calibrator_2',
            $savedCal2Id ?? ($isNewVerification ? ($reportDefaults[1] ?? null) : null)
        );

        if ($selectedCal1 && ! $options1->contains('id', (int) $selectedCal1)) {
            $selectedCal1 = null;
        }
        if ($selectedCal2 && ! $options2->contains('id', (int) $selectedCal2)) {
            $selectedCal2 = null;
        }

        $calibratorsPointsMap = [];
        foreach ($options1->concat($options2) as $equip) {
            if ($equip && ! isset($calibratorsPointsMap[$equip->id])) {
                $calibratorsPointsMap[$equip->id] = $this->getActiveCertificatePoints($equip->id);
            }
        }

        return [
            'options1' => $options1->values(),
            'options2' => $options2->values(),
            'selectedCal1' => $selectedCal1,
            'selectedCal2' => $selectedCal2,
            'calibratorsPointsMap' => $calibratorsPointsMap,
        ];
    }

    /**
     * استخراج نقاط المعايرة المعتمدة لشهادة الجهاز السارية لاستخدامها في الاستيفاء المترولوجي
     *
     * @return array<int, array{nominal: float, correction: float, uncertainty: float}>
     */
    public function getActiveCertificatePoints(int|string|null $equipmentId): array
    {
        if (! $equipmentId) {
            return [];
        }

        $equipment = Equipment::find($equipmentId);
        if (! $equipment) {
            return [];
        }

        $certificate = $equipment->calibrationCertificates()
            ->valid()
            ->latest('calibration_date')
            ->with('calibrationPoints')
            ->first()
            ?? $equipment->calibrationCertificates()
                ->latest('calibration_date')
                ->with('calibrationPoints')
                ->first();

        if (! $certificate || $certificate->calibrationPoints->isEmpty()) {
            return [];
        }

        return $certificate->calibrationPoints
            ->sortBy('nominal_value')
            ->values()
            ->map(fn ($p) => [
                'nominal' => (float) $p->nominal_value,
                'value' => (float) $p->correction,
                'correction' => (float) $p->correction,
                'uncertainty' => (float) ($p->uncertainty ?? 0.0),
            ])
            ->toArray();
    }

    /**
     * مرسلات القياس (Transmitters):
     * - Calibrateur 1: عائلة أجهزة الضغط أو الحرارة الحاملة لشهادة معايرة
     * - Calibrateur 2: عائلة أجهزة قياس التيار والملتيميتر (4-20 mA) الحاملة لشهادة معايرة
     */
    public function resolveForTransmitter(Report $report, Instrument $instrument, ?TransmitterVerification $verification): array
    {
        $specifications = $instrument->specifications->first();
        $measurand = $specifications?->grandeur?->name ?? 'Pression';
        $isTemp = stripos($measurand, 'Temp') !== false
                          || stripos($instrument->process_variable?->value ?? (string) $instrument->process_variable, 'Temp') !== false;

        $mt = $instrument->measurement_type ?? '';
        $tag = $instrument->tag_number ?? '';
        $isDP = ($mt === 'Differential') || stripos($tag, 'PDT') !== false || stripos($tag, 'DP') !== false || stripos($measurand, 'Diff') !== false;

        $family1 = $isTemp
            ? 'transmitter_temperature'
            : ($isDP ? 'dp_pressure' : 'pressure');

        $family2 = 'electrical';

        return $this->resolveDecoupledCalibrators($report, $instrument, $verification, $family1, $family2);
    }

    /**
     * المسابير الحرارية (Probes):
     * - Calibrateur 1: عائلة المراجع الحرارية الحاملة لشهادة معايرة (probe_thermal)
     * - Calibrateur 2: عائلة قياس وصناديق المقاومة الحاملة لشهادة معايرة (probe_resistance)
     */
    public function resolveForProbe(Report $report, Instrument $instrument, ?ProbeVerification $verification): array
    {
        return $this->resolveDecoupledCalibrators($report, $instrument, $verification, 'probe_thermal', 'probe_resistance');
    }

    /**
     * حاسبات التدفق (Flow Computers):
     * - Calibrateur 1: عائلة حوافن ومحاكيات التيار 4-20 mA (electrical)
     * - Calibrateur 2: عائلة معيار القناة المحاكاة: PT (ضغط), PDT (ضغط تفاضلي), أو TT (حرارة)
     */
    public function resolveForFlowComputer(
        Report $report,
        Instrument $instrument,
        ?FlowComputerVerification $verification,
        ?Instrument $activeTransmitter
    ): array {
        $activeGrandeur = $activeTransmitter?->specifications->first()?->grandeur?->name ?? 'Pression';
        $activeSymbol = $activeTransmitter?->specifications->first()?->grandeur?->symbol ?? 'bar';
        $pv = $activeTransmitter?->process_variable?->value ?? (string) $activeTransmitter?->process_variable;
        $mt = $activeTransmitter?->measurement_type ?? '';
        $tag = $activeTransmitter?->tag_number ?? '';

        $isTempChannel = ($pv === 'Temperature')
                          || stripos($activeGrandeur, 'Temp') !== false
                          || in_array(strtolower((string) $activeSymbol), ['°c', 'c', 'k']);

        $isDPChannel = ($mt === 'Differential')
                          || stripos($tag, 'PDT') !== false
                          || stripos($tag, 'DP') !== false
                          || stripos($activeGrandeur, 'Diff') !== false;

        $family1 = 'electrical';
        $family2 = $isTempChannel
            ? 'transmitter_temperature'
            : ($isDPChannel ? 'dp_pressure' : 'pressure');

        return $this->resolveDecoupledCalibrators($report, $instrument, $verification, $family1, $family2, $activeTransmitter);
    }
}
