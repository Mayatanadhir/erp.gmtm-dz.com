<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\AccuracyType;
use App\Enums\CalibrationCertificateStatus;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentStatus;
use App\Enums\FluidType;
use App\Enums\GrandeurType;
use App\Enums\ProcessVariable;
use App\Models\CalibrationCertificate;
use App\Models\CalibrationPoint;
use App\Models\Equipment;
use App\Models\EquipmentSpecification;
use App\Models\FlowComputerVerificationPoint;
use App\Models\Grandeur;
use App\Models\Instrument;
use App\Models\InstrumentSpecification;
use App\Models\Mission;
use App\Models\ProbeVerificationPoint;
use App\Models\Report;
use App\Models\Site;
use App\Models\TransmitterVerification;
use App\Models\TransmitterVerificationPoint;
use App\Models\User;
use App\Services\CalibratorResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransmitterCalibratorInterpolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Report $report;

    private Instrument $instrument;

    private Equipment $calibrator1;

    private Equipment $calibrator2;

    private Grandeur $grandeur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($superRole);

        $site = Site::create([
            'site_code' => 'ST-01',
            'full_name' => 'Hassi R\'mel Compressor Station',
            'short_name' => 'HRM',
        ]);

        $mission = Mission::create([
            'reference' => 'MIS-2026-INT',
            'site_id' => $site->id,
            'status' => 'active',
        ]);

        $this->report = Report::create([
            'report_number' => 'RPT-2026-INTERP-01',
            'mission_id' => $mission->id,
            'category' => 'instruments',
            'status' => 'progress',
            'created_by' => $this->user->id,
        ]);

        $this->grandeur = Grandeur::create([
            'name' => 'Pression Relative',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);

        $this->instrument = Instrument::create([
            'tag_number' => 'PT-2026-INT-01',
            'serial_number' => 'SN-PT-INT-99',
            'instrument_type' => 'transmitter',
            'site_id' => $site->id,
            'report_id' => $this->report->id,
            'fluid_type' => FluidType::Liquid,
            'technology' => 'Traditional',
        ]);

        InstrumentSpecification::create([
            'instrument_id' => $this->instrument->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => 0.0,
            'range_max' => 10.0, // 0 - 10 bar, span = 10 bar
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.5, // EMT = 0.5% * 10 = 0.05 bar
        ]);

        // Calibrator 1: Pressure Calibrator (Generator)
        $this->calibrator1 = Equipment::create([
            'internal_code' => 'CAL-PRES-01',
            'full_name' => 'Additel 761 Pressure Calibrator',
            'serial_number' => 'SN-CAL-P-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
        ]);

        $cert1 = CalibrationCertificate::create([
            'reference' => 'CERT-PRES-2026',
            'equipment_id' => $this->calibrator1->id,
            'calibration_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonths(11)->toDateString(),
            'status' => CalibrationCertificateStatus::Approved,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert1->id,
            'nominal_value' => 0.0,
            'reading_value' => 0.005,
            'correction' => 0.005,
            'uncertainty' => 0.001,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert1->id,
            'nominal_value' => 10.0,
            'reading_value' => 10.025,
            'correction' => 0.025,
            'uncertainty' => 0.002,
        ]);

        // Calibrator 2: Multimeter / mA Calibrator
        $this->calibrator2 = Equipment::create([
            'internal_code' => 'CAL-ELEC-01',
            'full_name' => 'Fluke 754 Documenting Calibrator',
            'serial_number' => 'SN-CAL-E-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
        ]);

        $cert2 = CalibrationCertificate::create([
            'reference' => 'CERT-ELEC-2026',
            'equipment_id' => $this->calibrator2->id,
            'calibration_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addMonths(11)->toDateString(),
            'status' => CalibrationCertificateStatus::Approved,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert2->id,
            'nominal_value' => 4.0,
            'reading_value' => 3.998,
            'correction' => 0.002, // at 4 mA: +0.002 mA correction
            'uncertainty' => 0.0005,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert2->id,
            'nominal_value' => 20.0,
            'reading_value' => 19.990,
            'correction' => 0.010, // at 20 mA: +0.010 mA correction
            'uncertainty' => 0.0008,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $this->calibrator1->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => 0.0,
            'range_max' => 20.0,
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.05,
        ]);

        $currentGrandeur = Grandeur::create([
            'code' => 'ELEC_MA',
            'name' => 'Courant',
            'unit' => 'mA',
            'symbol' => 'mA',
            'type' => GrandeurType::Measurement,
            'process_variable' => ProcessVariable::Pressure,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $this->calibrator2->id,
            'grandeur_id' => $currentGrandeur->id,
            'range_min' => 0.0,
            'range_max' => 24.0,
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.02,
        ]);

        $mission->equipments()->attach([$this->calibrator1->id, $this->calibrator2->id]);
    }

    public function test_calibrator_resolution_service_retrieves_active_certificate_points(): void
    {
        $service = app(CalibratorResolutionService::class);
        $points1 = $service->getActiveCertificatePoints($this->calibrator1->id);
        $points2 = $service->getActiveCertificatePoints($this->calibrator2->id);

        $this->assertCount(2, $points1);
        $this->assertEquals(0.0, $points1[0]['nominal']);
        $this->assertEquals(0.005, $points1[0]['correction']);
        $this->assertEquals(10.0, $points1[1]['nominal']);
        $this->assertEquals(0.025, $points1[1]['correction']);

        $this->assertCount(2, $points2);
        $this->assertEquals(4.0, $points2[0]['nominal']);
        $this->assertEquals(0.002, $points2[0]['correction']);
        $this->assertEquals(20.0, $points2[1]['nominal']);
        $this->assertEquals(0.010, $points2[1]['correction']);
    }

    public function test_transmitter_saisie_page_loads_with_calibrators_points_map(): void
    {
        $url = route('metrology.reports.saisie', [
            'report' => $this->report->id,
            'instrument' => $this->instrument->id,
        ]);

        $response = $this->actingAs($this->user)->get($url);
        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-instruments.transmitter');
        $response->assertViewHas('calibratorsPointsMap');

        $map = $response->viewData('calibratorsPointsMap');
        $this->assertArrayHasKey($this->calibrator1->id, $map);
        $this->assertArrayHasKey($this->calibrator2->id, $map);
        $this->assertCount(2, $map[$this->calibrator1->id]);
        $this->assertCount(2, $map[$this->calibrator2->id]);
    }

    public function test_store_transmitter_saisie_interpolates_corrections_and_saves_verification_points(): void
    {
        $url = route('metrology.reports.saisie.transmitter', [
            'report' => $this->report->id,
            'instrument' => $this->instrument->id,
        ]);

        // Standard 10 points (5 ascending + 5 descending)
        // Point 3 is 50%: 5.0 bar, 12.0 mA
        // Target: 5.0 bar -> Interpolated correction = 0.005 + 0.5 * (0.025 - 0.005) = 0.015 bar
        // Corrected reference = 5.0 + 0.015 = 5.015 bar
        // Measured signal: 12.0 mA -> Interpolated correction = 0.002 + 0.5 * (0.010 - 0.002) = 0.006 mA
        // Corrected signal = 12.0 + 0.006 = 12.006 mA
        // Physical measured value = 0 + ((12.006 - 4) / 16) * 10 = 5.00375 bar
        // Error = 5.00375 - 5.015 = -0.01125 bar
        $percentages = [0, 25, 50, 75, 100, 100, 75, 50, 25, 0];
        $points = [];
        foreach ($percentages as $pct) {
            $ref = ($pct / 100) * 10.0;
            $sig = 4.0 + ($pct / 100) * 16.0;
            $points[] = [
                'applied_percentage' => (float) $pct,
                'reference_value' => (float) $ref,
                'measured_signal' => (float) $sig,
                'indicated_value' => null,
            ];
        }

        $payload = [
            'verification_date' => now()->toDateString(),
            'calibrator_1' => $this->calibrator1->id,
            'calibrator_2' => $this->calibrator2->id,
            'points' => $points,
        ];

        $response = $this->actingAs($this->user)->post($url, $payload);
        $response->assertRedirect(route('metrology.reports.saisie', ['report' => $this->report->id, 'instrument' => $this->instrument->id]));

        $verification = TransmitterVerification::where('report_mission_id', $this->report->id)
            ->where('instrument_id', $this->instrument->id)
            ->first();

        $this->assertNotNull($verification);
        $savedPoints = $verification->points()->orderBy('step_order')->get();
        $this->assertCount(10, $savedPoints);

        // Verify the 50% point (index 2)
        $point50 = $savedPoints[2];
        $this->assertEqualsWithDelta(0.015, (float) $point50->calibrator_1_correction, 0.0001);
        $this->assertEqualsWithDelta(5.015, (float) $point50->corrected_reference_value, 0.0001);
        $this->assertEqualsWithDelta(0.006, (float) $point50->calibrator_2_correction, 0.0001);
        $this->assertEqualsWithDelta(12.006, (float) $point50->corrected_signal, 0.0001);

        // Verification point model calculated value attribute
        $this->assertEqualsWithDelta(5.00375, $point50->calculated_value, 0.0001);
        // Absolute error evaluated against corrected reference value
        $this->assertEqualsWithDelta(-0.01125, (float) $point50->absolute_error, 0.0005);
        $this->assertTrue($point50->is_conforme);
    }

    public function test_curve_and_pdf_reports_reflect_calibrator_interpolated_points(): void
    {
        // Save verification with interpolated point
        $verification = TransmitterVerification::create([
            'report_mission_id' => $this->report->id,
            'instrument_id' => $this->instrument->id,
            'verification_date' => now()->toDateString(),
            'overall_status' => true,
        ]);

        TransmitterVerificationPoint::create([
            'verification_id' => $verification->id,
            'step_order' => 1,
            'cycle_phase' => 'Ascending',
            'applied_percentage' => 50.0,
            'reference_value' => 5.0,
            'calibrator_1_correction' => 0.015,
            'corrected_reference_value' => 5.015,
            'measured_signal' => 12.0,
            'calibrator_2_correction' => 0.006,
            'corrected_signal' => 12.006,
            'indicated_value' => null,
            'absolute_error' => -0.01125,
            'emt_limit' => 0.05,
            'is_conforme' => true,
        ]);

        $verification->syncCalibratorByRole($this->calibrator1->id, 1);
        $verification->syncCalibratorByRole($this->calibrator2->id, 2);

        // Test curve view
        $curveUrl = route('metrology.reports.curve', [
            'report' => $this->report->id,
            'instrument' => $this->instrument->id,
        ]);

        $curveResponse = $this->actingAs($this->user)->get($curveUrl);
        $curveResponse->assertStatus(200);
        $curveResponse->assertViewIs('metrology.reports.curve');
        $curveResponse->assertSee('PT-2026-INT-01');

        // Test PDF summary report
        $summaryResponse = $this->actingAs($this->user)->get(route('metrology.reports.pdf.summary', $this->report->id));
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertHeader('content-type', 'application/pdf');

        // Test PDF detailed report
        $detailedResponse = $this->actingAs($this->user)->get(route('metrology.reports.pdf', $this->report->id));
        $detailedResponse->assertStatus(200);
        $detailedResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_calibrator_without_matching_grandeur_is_excluded_from_dropdown_options(): void
    {
        // An equipment with NO specifications configured
        $unqualifiedEquipment = Equipment::create([
            'internal_code' => 'TOOL-NO-SPEC',
            'full_name' => 'Unqualified Calibrator Without Specs',
            'serial_number' => 'SN-NO-SPEC',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
            'requires_calibration' => 1,
        ]);

        // Another equipment that has only Temperature specifications (not Pressure, not Current)
        $tempGrandeur = Grandeur::create([
            'code' => 'TEMP_C',
            'name' => 'Température',
            'unit' => '°C',
            'symbol' => '°C',
            'type' => GrandeurType::Measurement,
            'process_variable' => ProcessVariable::Temperature,
        ]);

        $tempEquipment = Equipment::create([
            'internal_code' => 'CAL-TEMP-ONLY',
            'full_name' => 'Temperature Only Standard',
            'serial_number' => 'SN-TEMP-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
            'requires_calibration' => 1,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $tempEquipment->id,
            'grandeur_id' => $tempGrandeur->id,
            'range_min' => -50.0,
            'range_max' => 200.0,
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.1,
        ]);

        $this->report->mission->equipments()->attach([$unqualifiedEquipment->id, $tempEquipment->id]);

        $service = app(CalibratorResolutionService::class);
        $resolved = $service->resolveForTransmitter($this->report, $this->instrument, null);

        // options1 (Generator for Pressure) must contain calibrator1 (Pressure), but NOT unqualified or tempEquipment
        $this->assertTrue($resolved['options1']->contains('id', $this->calibrator1->id));
        $this->assertFalse($resolved['options1']->contains('id', $unqualifiedEquipment->id));
        $this->assertFalse($resolved['options1']->contains('id', $tempEquipment->id));

        // options2 (Multimeter mA) must contain calibrator2 (Courant), but NOT unqualified or tempEquipment or calibrator1 (Pressure only)
        $this->assertTrue($resolved['options2']->contains('id', $this->calibrator2->id));
        $this->assertFalse($resolved['options2']->contains('id', $unqualifiedEquipment->id));
        $this->assertFalse($resolved['options2']->contains('id', $tempEquipment->id));
        $this->assertFalse($resolved['options2']->contains('id', $this->calibrator1->id));
    }

    public function test_calibrator_not_attached_to_mission_is_strictly_excluded_from_dropdown_options(): void
    {
        // Create an equipment with valid Pressure specifications, but NOT attached to this mission
        $externalPressureEquip = Equipment::create([
            'internal_code' => 'CAL-PRES-EXTERNAL',
            'full_name' => 'External Non-Mission Pressure Calibrator',
            'serial_number' => 'SN-PRES-EXT-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
            'requires_calibration' => 1,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $externalPressureEquip->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => 0.0,
            'range_max' => 50.0,
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.05,
        ]);

        $service = app(CalibratorResolutionService::class);
        $resolved = $service->resolveForTransmitter($this->report, $this->instrument, null);

        // Ensure calibrator1 (which is attached to mission) is included
        $this->assertTrue($resolved['options1']->contains('id', $this->calibrator1->id));

        // Ensure external pressure calibrator (not attached to mission) is strictly excluded
        $this->assertFalse($resolved['options1']->contains('id', $externalPressureEquip->id));

        // Now attach it to the mission and verify it becomes available
        $this->report->mission->equipments()->attach($externalPressureEquip->id);
        $resolvedAfterAttach = $service->resolveForTransmitter($this->report, $this->instrument, null);
        $this->assertTrue($resolvedAfterAttach['options1']->contains('id', $externalPressureEquip->id));
    }

    public function test_saved_calibrator_from_outside_mission_is_not_injected_into_dropdown(): void
    {
        $nonMissionEquip = Equipment::create([
            'internal_code' => 'CAL-SAVED-NON-MISSION',
            'full_name' => 'Saved Calibrator Not In Mission',
            'serial_number' => 'SN-SAVED-EXT-99',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
            'requires_calibration' => 1,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $nonMissionEquip->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => 0.0,
            'range_max' => 10.0,
            'accuracy_type' => AccuracyType::Percentage,
            'accuracy_value' => 0.05,
        ]);

        $verification = TransmitterVerification::create([
            'report_mission_id' => $this->report->id,
            'instrument_id' => $this->instrument->id,
            'ambient_temperature' => 20.0,
            'ambient_humidity' => 50.0,
            'ambient_pressure' => 1013.25,
        ]);

        $verification->syncCalibratorByRole($nonMissionEquip->id, 1);

        $service = app(CalibratorResolutionService::class);
        $resolved = $service->resolveForTransmitter($this->report, $this->instrument, $verification);

        // Options must NOT leak the non-mission equipment
        $this->assertFalse($resolved['options1']->contains('id', $nonMissionEquip->id));
        // Selected calibrator must reset to null rather than pointing to non-mission equipment
        $this->assertNull($resolved['selectedCal1']);
    }

    public function test_report_without_mission_returns_empty_dropdown_options(): void
    {
        $reportNoMission = Report::create([
            'report_number' => 'RPT-NO-MISSION-01',
            'mission_id' => null,
            'category' => 'instruments',
            'status' => 'progress',
            'created_by' => $this->user->id,
        ]);

        $service = app(CalibratorResolutionService::class);
        $allEquips = $service->getCertifiedMissionEquipments($reportNoMission);
        $this->assertTrue($allEquips->isEmpty());

        $resolved = $service->resolveForTransmitter($reportNoMission, $this->instrument, null);
        $this->assertCount(0, $resolved['options1']);
        $this->assertCount(0, $resolved['options2']);
    }

    public function test_probe_calibrator_resolution_and_view_data(): void
    {
        $service = app(CalibratorResolutionService::class);
        $probeInstrument = Instrument::create([
            'site_id' => $this->instrument->site_id,
            'tag_number' => 'TT-PROBE-01',
            'serial_number' => 'SN-PROBE-01',
            'instrument_type' => 'probe',
            'measurement_type' => 'Direct',
            'process_variable' => ProcessVariable::Temperature,
        ]);
        InstrumentSpecification::create([
            'instrument_id' => $probeInstrument->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => -50.0,
            'range_max' => 200.0,
            'accuracy_value' => 0.15,
            'accuracy_type' => AccuracyType::Absolute,
        ]);

        $resolved = $service->resolveForProbe($this->report, $probeInstrument, null);
        $this->assertArrayHasKey('options1', $resolved);
        $this->assertArrayHasKey('options2', $resolved);
        $this->assertArrayHasKey('calibratorsPointsMap', $resolved);

        $response = $this->actingAs($this->user)->get(route('metrology.reports.saisie', [
            'report' => $this->report->id,
            'instrument' => $probeInstrument->id,
        ]));
        $response->assertOk();
        $response->assertSee(__('Applied Temp (°C)'));
        $response->assertSee(__('correction (Interpolated)'));
        $response->assertSee('cell-temp-corrigee', false);
        $response->assertSee('cell-res-corrigee', false);
    }

    public function test_probe_saisie_stores_calibrator_corrections_and_corrected_temperatures(): void
    {
        $probeInstrument = Instrument::create([
            'site_id' => $this->instrument->site_id,
            'tag_number' => 'TT-PROBE-02',
            'serial_number' => 'SN-PROBE-02',
            'instrument_type' => 'probe',
            'measurement_type' => 'Direct',
            'process_variable' => ProcessVariable::Temperature,
        ]);
        InstrumentSpecification::create([
            'instrument_id' => $probeInstrument->id,
            'grandeur_id' => $this->grandeur->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.15,
            'accuracy_type' => AccuracyType::Absolute,
        ]);

        $postData = [
            'verification_date' => now()->format('Y-m-d'),
            'calibrator_1' => $this->calibrator1->id,
            'calibrator_2' => $this->calibrator2->id,
            'points' => [
                [
                    'reference_temperature' => 0.0,
                    'calibrator_1_correction' => 0.05,
                    'corrected_reference_temperature' => 0.05,
                    'measured_resistance' => 100.0,
                    'calibrator_2_correction' => 0.0,
                    'corrected_measured_resistance' => 100.0,
                    'indicated_temperature' => 0.0,
                ],
                [
                    'reference_temperature' => 25.0,
                    'calibrator_1_correction' => 0.05,
                    'corrected_reference_temperature' => 25.05,
                    'measured_resistance' => 109.735,
                    'calibrator_2_correction' => 0.0,
                    'corrected_measured_resistance' => 109.735,
                    'indicated_temperature' => 25.0,
                ],
                [
                    'reference_temperature' => 50.0,
                    'calibrator_1_correction' => 0.05,
                    'corrected_reference_temperature' => 50.05,
                    'measured_resistance' => 119.397,
                    'calibrator_2_correction' => 0.0,
                    'corrected_measured_resistance' => 119.397,
                    'indicated_temperature' => 50.0,
                ],
                [
                    'reference_temperature' => 75.0,
                    'calibrator_1_correction' => 0.05,
                    'corrected_reference_temperature' => 75.05,
                    'measured_resistance' => 128.987,
                    'calibrator_2_correction' => 0.0,
                    'corrected_measured_resistance' => 128.987,
                    'indicated_temperature' => 75.0,
                ],
                [
                    'reference_temperature' => 100.0,
                    'calibrator_1_correction' => 0.05,
                    'corrected_reference_temperature' => 100.05,
                    'measured_resistance' => 138.506,
                    'calibrator_2_correction' => 0.0,
                    'corrected_measured_resistance' => 138.506,
                    'indicated_temperature' => 100.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post(
            route('metrology.reports.saisie.probe', [
                'report' => $this->report->id,
                'instrument' => $probeInstrument->id,
            ]),
            $postData
        );

        $response->assertRedirect(route('metrology.reports.saisie', [
            'report' => $this->report->id,
            'instrument' => $probeInstrument->id,
        ]));

        $this->assertDatabaseHas('probe_verification_points', [
            'step_order' => 1,
            'reference_temperature' => 0.0,
        ]);
        $point = ProbeVerificationPoint::where('step_order', 1)->first();
        $this->assertNotNull($point->corrected_reference_temperature);
        $this->assertNotNull($point->calibrator_1_correction);
        $this->assertNotNull($point->calibrator_2_correction);
        $this->assertNotNull($point->corrected_measured_resistance);
    }

    public function test_flow_computer_calibrator_resolution_and_view_data(): void
    {
        $service = app(CalibratorResolutionService::class);
        $fcInstrument = Instrument::create([
            'site_id' => $this->instrument->site_id,
            'tag_number' => 'FC-01',
            'serial_number' => 'SN-FC-01',
            'instrument_type' => 'flow_computer',
            'measurement_type' => 'Direct',
            'process_variable' => ProcessVariable::Volume,
        ]);

        $fcInstrument->linkedTransmitters()->attach($this->instrument->id, ['channel_number' => 'Ch1']);

        $resolved = $service->resolveForFlowComputer($this->report, $fcInstrument, null, $this->instrument);
        $this->assertArrayHasKey('options1', $resolved);
        $this->assertArrayHasKey('options2', $resolved);
        $this->assertArrayHasKey('calibratorsPointsMap', $resolved);

        $response = $this->actingAs($this->user)->get(route('metrology.reports.saisie', [
            'report' => $this->report->id,
            'instrument' => $fcInstrument->id,
            'transmitter_id' => $this->instrument->id,
        ]));
        $response->assertOk();
        $response->assertSee('cell-signal-corrigee', false);
        $response->assertSee('cell-val-corrigee', false);
        $response->assertSee(__('correction (Interpolated)'));
    }

    public function test_flow_computer_saisie_stores_calibrator_corrections_and_corrected_values(): void
    {
        $fcInstrument = Instrument::create([
            'site_id' => $this->instrument->site_id,
            'tag_number' => 'FC-02',
            'serial_number' => 'SN-FC-02',
            'instrument_type' => 'flow_computer',
            'measurement_type' => 'Direct',
            'process_variable' => ProcessVariable::Volume,
        ]);

        $fcInstrument->linkedTransmitters()->attach($this->instrument->id, ['channel_number' => 'Ch1']);

        $points = [];
        for ($i = 0; $i < 10; $i++) {
            $pct = $i < 5 ? $i * 25.0 : (9 - $i) * 25.0;
            $sig = 4.0 + ($pct / 100.0) * 16.0;
            $val = ($pct / 100.0) * 10.0;
            $points[] = [
                'applied_percentage' => $pct,
                'expected_signal' => $sig,
                'measured_signal' => $sig,
                'calibrator_1_correction' => 0.005,
                'corrected_signal' => $sig + 0.005,
                'expected_value' => $val,
                'calibrator_2_correction' => 0.01,
                'corrected_expected_value' => $val + 0.01,
                'indicated_value' => $val,
            ];
        }

        $postData = [
            'transmitter_id' => $this->instrument->id,
            'verification_date' => now()->format('Y-m-d'),
            'shunt_resistance' => 250.0,
            'calibrator_1' => $this->calibrator1->id,
            'calibrator_2' => $this->calibrator2->id,
            'points' => $points,
        ];

        $response = $this->actingAs($this->user)->post(
            route('metrology.reports.saisie.flow_computer', [
                'report' => $this->report->id,
                'instrument' => $fcInstrument->id,
            ]),
            $postData
        );

        $response->assertRedirect(route('metrology.reports.saisie', [
            'report' => $this->report->id,
            'instrument' => $fcInstrument->id,
            'transmitter_id' => $this->instrument->id,
        ]));

        $this->assertDatabaseHas('flow_computer_verification_points', [
            'step_order' => 1,
            'measured_signal' => 4.0,
        ]);

        $savedPoint = FlowComputerVerificationPoint::where('step_order', 1)->first();
        $this->assertNotNull($savedPoint);
        $this->assertNotNull($savedPoint->calibrator_1_correction);
        $this->assertNotNull($savedPoint->corrected_signal);
        $this->assertNotNull($savedPoint->calibrator_2_correction);
        $this->assertNotNull($savedPoint->corrected_expected_value);
    }
}
