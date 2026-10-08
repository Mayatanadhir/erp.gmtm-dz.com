<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Equipment;
use App\Models\FlowComputerVerification;
use App\Models\Instrument;
use App\Models\ProbeVerification;
use App\Models\Report;
use App\Models\TransmitterVerification;
use Illuminate\Support\Collection;

final class CalibratorResolutionService
{
    /**
     * جلب جميع الأجهزة المعتمدة والمصحوبة بشهادات معايرة (requires_calibration = 1)
     * التابعة للمهمة الحالية (أو في النظام عند عدم توفر مهمة)
     */
    public function getCertifiedMissionEquipments(Report $report): Collection
    {
        $mission = $report->mission;

        if ($mission) {
            return $mission->equipments()
                ->whereNotIn('equipment.category', ['vehicle', 'Vehicle', 'work_tool'])
                ->where(function ($q) {
                    $q->whereIn('equipment.requires_calibration', ['1', 1, true])
                        ->orWhere('equipment.category', 'measuring_instrument');
                })
                ->with(['specifications.grandeur', 'grandeurs', 'calibrationCertificates'])
                ->get();
        }

        return Equipment::whereNotIn('category', ['vehicle', 'Vehicle', 'work_tool'])
            ->where(function ($q) {
                $q->whereIn('requires_calibration', ['1', 1, true])
                    ->orWhere('category', 'measuring_instrument');
            })
            ->with(['specifications.grandeur', 'grandeurs', 'calibrationCertificates'])
            ->get();
    }

    /**
     * فلترة الأجهزة حسب العائلة المترولوجية المعتمدة بشهادة
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

            return match ($family) {
                // 1. عائلة أجهزة معايرة الضغط (Pressure References)
                'pressure' => (function () use ($equip, $text) {
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Press') !== false || stripos($g->symbol ?? '', 'bar') !== false)) {
                        return true;
                    }

                    return (bool) preg_match('/press|bar|pao|dpc|manom|adt.*672|druck/iu', $text);
                })(),

                // 2. عائلة أجهزة الضغط التفاضلي (DP References)
                'dp_pressure' => (function () use ($equip, $text) {
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Press') !== false || stripos($g->symbol ?? '', 'bar') !== false || stripos($g->symbol ?? '', 'mbar') !== false)) {
                        if (preg_match('/dp|diff|Δp|-1.*1|adt.*672dp/iu', $text)) {
                            return true;
                        }
                    }

                    return (bool) preg_match('/dp|diff[eé]rentiel|Δp|\-1.*1\s*bar|mbar|adt.*672dp/iu', $text);
                })(),

                // 3. عائلة أجهزة قياس وتوليد التيار 4-20 mA والملتيميتر (Electrical References)
                'electrical' => (function () use ($equip, $text) {
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Courant') !== false || stripos($g->symbol ?? '', 'mA') !== false)) {
                        return true;
                    }

                    return (bool) preg_match('/multi|courant|amp|process|adt.*221|adt.*223|fluke.*754|fluke.*744|beamex/iu', $text);
                })(),

                // 4. عائلة أجهزة ومولدات الحرارة لمرسلات الحرارة (Temperature Generators)
                'transmitter_temperature' => (function () use ($equip, $text) {
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Temp') !== false || stripos($g->symbol ?? '', '°c') !== false)) {
                        return true;
                    }

                    return (bool) preg_match('/calibrateur.*process|simulateur|boite.*decade|boîte.*décade|adt.*221|adt.*223|adt.*282|chaine.*temp|testo/iu', $text);
                })(),

                // 5. عائلة المراجع الحرارية للمسابير (Cal. 1: Bain/Four/Chaine de temp)
                'probe_thermal' => (function () use ($equip, $text) {
                    if (preg_match('/r[eé]sistance[^a-z]*[eé]talon|boite[^a-z]*decade|boîte[^a-z]*décade/iu', $text)) {
                        return false;
                    }
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Temp') !== false || stripos($g->symbol ?? '', '°c') !== false)) {
                        return true;
                    }

                    return (bool) preg_match('/chaine.*temp|isotech|wika|testo|adt.*282|four|bloc.*sec|bain|adt.*221|adt.*223/iu', $text);
                })(),

                // 6. عائلة قياس وصناديق المقاومة للمسابير (Cal. 2: Résistance/Ω/Décades)
                'probe_resistance' => (function () use ($equip, $text) {
                    if ($equip->grandeurs && $equip->grandeurs->contains(fn ($g) => stripos($g->name ?? '', 'Resist') !== false || stripos($g->name ?? '', 'Résist') !== false || stripos($g->symbol ?? '', 'ohm') !== false || stripos($g->symbol ?? '', 'Ω') !== false)) {
                        return true;
                    }

                    return (bool) preg_match('/r[eé]sist|ohm|omh|pont|boite.*decade|boîte.*décade|adt.*221|adt.*223|adt.*282/iu', $text);
                })(),

                default => true,
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

        // 2. جلب وتصفية أجهزة المهمة الحاملة لشهادات المعايرة حسب العائلة
        $allEquips = $this->getCertifiedMissionEquipments($report);
        $options1 = $this->filterByFamily($allEquips, $family1);
        $options2 = $this->filterByFamily($allEquips, $family2);

        // 3. ضمان وجود الجهاز المحفوظ في القائمة حتى في الحالات الاستثنائية
        if ($savedCal1Id) {
            $cal1Obj = Equipment::find($savedCal1Id);
            if ($cal1Obj && ! $options1->contains('id', $savedCal1Id)) {
                $options1 = $options1->prepend($cal1Obj);
            }
        }
        if ($savedCal2Id) {
            $cal2Obj = Equipment::find($savedCal2Id);
            if ($cal2Obj && ! $options2->contains('id', $savedCal2Id)) {
                $options2 = $options2->prepend($cal2Obj);
            }
        }

        // 4. تحديد القيمة المحددة: old() -> المحفوظ في DB -> الافتراضي للتقرير
        $selectedCal1 = old(
            'calibrator_1',
            $savedCal1Id ?? ($isNewVerification ? ($reportDefaults[0] ?? null) : null)
        );
        $selectedCal2 = old(
            'calibrator_2',
            $savedCal2Id ?? ($isNewVerification ? ($reportDefaults[1] ?? null) : null)
        );

        return [
            'options1' => $options1->values(),
            'options2' => $options2->values(),
            'selectedCal1' => $selectedCal1,
            'selectedCal2' => $selectedCal2,
        ];
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
