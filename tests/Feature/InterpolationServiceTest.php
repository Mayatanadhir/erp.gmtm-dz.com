<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccuracyType;
use App\Enums\CalibrationCertificateStatus;
use App\Enums\CalibrationPointStatus;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentPackage;
use App\Enums\EquipmentStatus;
use App\Enums\GrandeurType;
use App\Models\CalibrationCertificate;
use App\Models\CalibrationInterpolation;
use App\Models\CalibrationPoint;
use App\Models\Equipment;
use App\Models\EquipmentSpecification;
use App\Models\Grandeur;
use App\Models\User;
use App\Services\InterpolationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InterpolationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Equipment $equipment;

    protected CalibrationCertificate $certificate;

    protected InterpolationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($superRole);

        $this->equipment = Equipment::create([
            'full_name' => 'Pressure Transmitter PT-100',
            'short_name' => 'PT-100',
            'internal_code' => 'EQ-PT-001',
            'serial_number' => 'SN-998877',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $this->certificate = CalibrationCertificate::create([
            'reference' => 'CERT-2026-INTERP',
            'equipment_id' => $this->equipment->id,
            'status' => CalibrationCertificateStatus::Approved,
            'calibration_date' => Carbon::parse('2026-03-15'),
            'expiry_date' => Carbon::parse('2027-03-15'),
            'validity_period_months' => 12,
            'laboratory_name' => 'GMTM Metrology Center',
            'price' => 18000.00,
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        // Add 5 points: (0, 0.00, 0.02), (25, 0.05, 0.03), (50, 0.12, 0.04), (75, 0.08, 0.03), (100, 0.02, 0.02)
        $pointsData = [
            ['nominal_value' => 0.0, 'reading_value' => 0.0, 'correction' => 0.00, 'uncertainty' => 0.02],
            ['nominal_value' => 25.0, 'reading_value' => 24.95, 'correction' => 0.05, 'uncertainty' => 0.03],
            ['nominal_value' => 50.0, 'reading_value' => 49.88, 'correction' => 0.12, 'uncertainty' => 0.04],
            ['nominal_value' => 75.0, 'reading_value' => 74.92, 'correction' => 0.08, 'uncertainty' => 0.03],
            ['nominal_value' => 100.0, 'reading_value' => 99.98, 'correction' => 0.02, 'uncertainty' => 0.02],
        ];

        foreach ($pointsData as $pt) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $this->certificate->id,
                'nominal_value' => $pt['nominal_value'],
                'reading_value' => $pt['reading_value'],
                'correction' => $pt['correction'],
                'uncertainty' => $pt['uncertainty'],
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        $this->service = app(InterpolationService::class);
    }

    public function test_exact_point_matches_certificate_point_with_zero_modeling_uncertainty(): void
    {
        $cleanPoints = $this->service->getCleanPoints($this->certificate->calibrationPoints);

        // Target exactly at nominal 50.0
        $result = $this->service->interpolate($cleanPoints, 50.0, true);

        $this->assertTrue($result['is_exact_point']);
        $this->assertEquals(50.0, $result['target_x']);
        $this->assertEquals(0.12, $result['interpolated_value']);
        $this->assertEquals(0.04, $result['u_exp']);
        $this->assertEquals(0.0, $result['u_mod']);
        $this->assertEquals(0.04, $result['u_combined']);
        $this->assertEquals(0.08, $result['expanded_uncertainty']);
    }

    public function test_linear_interpolation_with_platel_model_computes_u_exp_and_u_mod(): void
    {
        $cleanPoints = $this->service->getCleanPoints($this->certificate->calibrationPoints);

        // Target between 25.0 and 50.0 -> at 37.5 (midpoint)
        $result = $this->service->interpolate($cleanPoints, 37.5, true);

        $this->assertFalse($result['is_exact_point']);
        $this->assertEquals(37.5, $result['target_x']);
        // y(37.5) = 0.05 + 0.5 * (0.12 - 0.05) = 0.085
        $this->assertEquals(0.085, $result['interpolated_value']);
        $this->assertGreaterThan(0, $result['u_exp']);
        $this->assertGreaterThan(0, $result['u_mod']);
        $this->assertGreaterThan(0, $result['expanded_uncertainty']);
        $this->assertEquals(
            round($result['interpolated_value'] + $result['expanded_uncertainty'], 5),
            $result['confidence_interval']['upper']
        );
        $this->assertEquals(
            round($result['interpolated_value'] - $result['expanded_uncertainty'], 5),
            $result['confidence_interval']['lower']
        );
    }

    public function test_directive_points_outside_min_and_max_bounds_are_ignored(): void
    {
        // Add points outside bounds (e.g. -20 and 150)
        $rawPoints = [
            ['nominal' => -20.0, 'value' => -0.1, 'uncertainty' => 0.05],
            ['nominal' => 0.0, 'value' => 0.00, 'uncertainty' => 0.02],
            ['nominal' => 50.0, 'value' => 0.12, 'uncertainty' => 0.04],
            ['nominal' => 100.0, 'value' => 0.02, 'uncertainty' => 0.02],
            ['nominal' => 150.0, 'value' => 0.50, 'uncertainty' => 0.10],
        ];

        // Directive: Points outside [0.0, 100.0] must be ignored
        $clean = $this->service->getCleanPoints($rawPoints, 0.0, 100.0);

        $this->assertCount(3, $clean);
        $this->assertEquals(0.0, $clean[0]['nominal']);
        $this->assertEquals(50.0, $clean[1]['nominal']);
        $this->assertEquals(100.0, $clean[2]['nominal']);
    }

    public function test_generate_five_point_grid_defaults_to_uniform_distribution(): void
    {
        $grid = $this->service->generateFivePointGrid($this->certificate);

        $this->assertTrue($grid['has_data']);
        $this->assertEquals(0.0, $grid['span_min']);
        $this->assertEquals(100.0, $grid['span_max']);
        $this->assertCount(5, $grid['points']);

        $targets = array_column($grid['points'], 'target_x');
        $this->assertEquals([0.0, 25.0, 50.0, 75.0, 100.0], $targets);
    }

    public function test_save_five_point_grid_persists_into_calibration_interpolations_table(): void
    {
        $points = [0.0, 20.0, 40.0, 70.0, 100.0];
        $saved = $this->service->saveFivePointGrid($this->certificate, $points);

        $this->assertCount(5, $saved);
        $this->assertDatabaseCount('calibration_interpolations', 5);

        $this->assertDatabaseHas('calibration_interpolations', [
            'calibration_certificate_id' => $this->certificate->id,
            'point_index' => 2,
            'target_nominal' => 20.0,
        ]);

        $firstInterp = CalibrationInterpolation::where('point_index', 2)->first();
        $this->assertNotNull($firstInterp);
        $this->assertEquals(
            round($firstInterp->interpolated_value + $firstInterp->expanded_uncertainty, 4),
            round($firstInterp->upper_limit, 4)
        );
    }

    public function test_controller_update_interpolation_grid_endpoint(): void
    {
        $payload = [
            'points' => [0.0, 20.0, 50.0, 80.0, 100.0],
            'min_bound' => 0.0,
            'max_bound' => 100.0,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.interpolation-grid', $this->certificate), $payload);

        $response->assertRedirect(route('metrology.calibration-certificates.show', [
            'certificate' => $this->certificate,
            'tab' => 'interpolation',
        ]));
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('calibration_interpolations', 5);
        $this->assertDatabaseHas('calibration_interpolations', [
            'calibration_certificate_id' => $this->certificate->id,
            'target_nominal' => 80.0,
        ]);
    }

    public function test_controller_show_renders_interpolation_tab_and_data(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.show', [
                'certificate' => $this->certificate,
                'tab' => 'interpolation',
            ]));

        $response->assertOk();
        $response->assertViewHas('fivePointGrid');
        $response->assertViewHas('comparisonData');
        $response->assertSee(__('Interpolation & 5-Point Curves'), false);
        $response->assertSee('singleCurveChart');
        $response->assertSee('comparisonCurveChart');
    }

    public function test_multi_specification_certificate_isolates_5_point_grids_and_units(): void
    {
        // 1. Create two Grandeurs (Pressure and Current)
        $grandeurPressure = Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);
        $grandeurCurrent = Grandeur::create([
            'name' => 'Courant',
            'symbol' => 'mA',
            'type' => GrandeurType::Measurement,
        ]);

        // 2. Create two EquipmentSpecifications
        $specPressure = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurPressure->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.1,
            'accuracy_type' => AccuracyType::Percentage,
        ]);
        $specCurrent = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurCurrent->id,
            'range_min' => 4.0,
            'range_max' => 20.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => AccuracyType::Percentage,
        ]);

        // 3. Create a new multi-parameter certificate
        $multiCert = CalibrationCertificate::create([
            'reference' => 'CERT-MULTI-SPEC',
            'equipment_id' => $this->equipment->id,
            'status' => CalibrationCertificateStatus::Approved,
            'calibration_date' => Carbon::parse('2026-04-01'),
            'expiry_date' => Carbon::parse('2027-04-01'),
            'validity_period_months' => 12,
            'laboratory_name' => 'GMTM Metrology Center',
            'price' => 25000.00,
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        // Add 3 points for Pressure (0, 50, 100)
        foreach ([0.0, 50.0, 100.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $multiCert->id,
                'equipment_specification_id' => $specPressure->id,
                'nominal_value' => $nom,
                'reading_value' => $nom + 0.05,
                'correction' => -0.05,
                'uncertainty' => 0.02,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // Add 3 points for Current (4, 12, 20)
        foreach ([4.0, 12.0, 20.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $multiCert->id,
                'equipment_specification_id' => $specCurrent->id,
                'nominal_value' => $nom,
                'reading_value' => $nom + 0.01,
                'correction' => -0.01,
                'uncertainty' => 0.005,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // 4. Test getCertificateSpecifications()
        $specs = $this->service->getCertificateSpecifications($multiCert);
        $this->assertCount(2, $specs);
        $this->assertTrue($specs->contains('id', $specPressure->id));
        $this->assertTrue($specs->contains('id', $specCurrent->id));

        // 5. Test grid generation isolation with unit and parameter names
        $gridPressure = $this->service->generateFivePointGrid($multiCert, null, null, null, $specPressure->id);
        $this->assertEquals('bar', $gridPressure['unit_symbol']);
        $this->assertEquals('Pression', $gridPressure['parameter_name']);
        $this->assertEquals(0.0, $gridPressure['span_min']);
        $this->assertEquals(100.0, $gridPressure['span_max']);
        $this->assertCount(5, $gridPressure['points']);

        $gridCurrent = $this->service->generateFivePointGrid($multiCert, null, null, null, $specCurrent->id);
        $this->assertEquals('mA', $gridCurrent['unit_symbol']);
        $this->assertEquals('Courant', $gridCurrent['parameter_name']);
        $this->assertEquals(4.0, $gridCurrent['span_min']);
        $this->assertEquals(20.0, $gridCurrent['span_max']);
        $this->assertCount(5, $gridCurrent['points']);

        // 6. Test saveFivePointGrid scoping per specification
        $savedPressure = $this->service->saveFivePointGrid($multiCert, [0.0, 25.0, 50.0, 75.0, 100.0], null, null, $specPressure->id);
        $this->assertCount(5, $savedPressure);
        $this->assertEquals(5, CalibrationInterpolation::where('equipment_specification_id', $specPressure->id)->count());

        $savedCurrent = $this->service->saveFivePointGrid($multiCert, [4.0, 8.0, 12.0, 16.0, 20.0], null, null, $specCurrent->id);
        $this->assertCount(5, $savedCurrent);

        // Assert 10 total saved in database without overwriting each other
        $this->assertEquals(10, CalibrationInterpolation::where('calibration_certificate_id', $multiCert->id)->count());
        $this->assertEquals(5, CalibrationInterpolation::where('equipment_specification_id', $specPressure->id)->count());
        $this->assertEquals(5, CalibrationInterpolation::where('equipment_specification_id', $specCurrent->id)->count());

        // Re-saving Pressure should only replace Pressure's 5 points
        $this->service->saveFivePointGrid($multiCert, [0.0, 20.0, 40.0, 60.0, 100.0], null, null, $specPressure->id);
        $this->assertEquals(10, CalibrationInterpolation::where('calibration_certificate_id', $multiCert->id)->count());
        $this->assertEquals(5, CalibrationInterpolation::where('equipment_specification_id', $specPressure->id)->count());
    }

    public function test_controller_handles_multi_specification_tabs_and_saving(): void
    {
        $grandeurPressure = Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);
        $grandeurCurrent = Grandeur::create([
            'name' => 'Courant',
            'symbol' => 'mA',
            'type' => GrandeurType::Measurement,
        ]);

        $specPressure = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurPressure->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.1,
            'accuracy_type' => AccuracyType::Percentage,
        ]);
        $specCurrent = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurCurrent->id,
            'range_min' => 4.0,
            'range_max' => 20.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => AccuracyType::Percentage,
        ]);

        $multiCert = CalibrationCertificate::create([
            'reference' => 'CERT-MULTI-CTRL',
            'equipment_id' => $this->equipment->id,
            'status' => CalibrationCertificateStatus::Approved,
            'calibration_date' => Carbon::parse('2026-04-01'),
            'expiry_date' => Carbon::parse('2027-04-01'),
            'validity_period_months' => 12,
            'laboratory_name' => 'GMTM Metrology Center',
            'price' => 25000.00,
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        foreach ([0.0, 50.0, 100.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $multiCert->id,
                'equipment_specification_id' => $specPressure->id,
                'nominal_value' => $nom,
                'reading_value' => $nom,
                'correction' => 0.0,
                'uncertainty' => 0.01,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }
        foreach ([4.0, 12.0, 20.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $multiCert->id,
                'equipment_specification_id' => $specCurrent->id,
                'nominal_value' => $nom,
                'reading_value' => $nom,
                'correction' => 0.0,
                'uncertainty' => 0.005,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // Test GET show with spec_id query
        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.show', [
                'certificate' => $multiCert,
                'tab' => 'interpolation',
                'spec_id' => $specCurrent->id,
            ]));

        $response->assertOk();
        $response->assertViewHas('activeSpecification', fn ($s) => $s->id === $specCurrent->id);
        $response->assertSee(__('Select Standard / Parameter'), false);
        $response->assertSee('mA');
        $response->assertSee('bar');

        // Test POST saving grid for specCurrent
        $postResponse = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.interpolation-grid', $multiCert), [
                'equipment_specification_id' => $specCurrent->id,
                'points' => [4.0, 8.0, 12.0, 16.0, 20.0],
                'min_bound' => 4.0,
                'max_bound' => 20.0,
            ]);

        $postResponse->assertRedirect(route('metrology.calibration-certificates.show', [
            'certificate' => $multiCert,
            'tab' => 'interpolation',
            'spec_id' => $specCurrent->id,
        ]));

        $this->assertEquals(5, CalibrationInterpolation::where([
            'calibration_certificate_id' => $multiCert->id,
            'equipment_specification_id' => $specCurrent->id,
        ])->count());
    }

    public function test_equipment_specifications_as_sole_source_distinguishing_measurement_from_source_with_same_grandeur(): void
    {
        // Create an equipment with two specifications having the same unit (°C) but different types:
        // 1. Temperature Measurement (Sensor / IN)
        // 2. Temperature Source (Generation / OUT)
        $multipurposeCalibrator = Equipment::create([
            'full_name' => 'Multifunction Temperature Calibrator MC-500',
            'short_name' => 'MC-500',
            'internal_code' => 'EQ-MC-500',
            'serial_number' => 'SN-MC-500-01',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot02,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $grandeurTempIn = Grandeur::create([
            'name' => 'Temperature Measurement',
            'symbol' => '°C',
            'type' => GrandeurType::Measurement,
            'is_active' => true,
        ]);

        $grandeurTempOut = Grandeur::create([
            'name' => 'Temperature Generation',
            'symbol' => '°C',
            'type' => GrandeurType::Source,
            'is_active' => true,
        ]);

        $specIn = EquipmentSpecification::create([
            'equipment_id' => $multipurposeCalibrator->id,
            'grandeur_id' => $grandeurTempIn->id,
            'range_min' => -50.0,
            'range_max' => 500.0,
            'accuracy_type' => AccuracyType::Absolute,
            'accuracy_value' => 0.05,
        ]);

        $specOut = EquipmentSpecification::create([
            'equipment_id' => $multipurposeCalibrator->id,
            'grandeur_id' => $grandeurTempOut->id,
            'range_min' => -20.0,
            'range_max' => 300.0,
            'accuracy_type' => AccuracyType::Absolute,
            'accuracy_value' => 0.1,
        ]);

        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-MC500-2026',
            'equipment_id' => $multipurposeCalibrator->id,
            'status' => CalibrationCertificateStatus::Approved,
            'calibration_date' => Carbon::parse('2026-05-10'),
            'expiry_date' => Carbon::parse('2027-05-10'),
            'validity_period_months' => 12,
            'laboratory_name' => 'GMTM Calibration Center',
            'price' => 35000.00,
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        // Add calibration points for Measurement (IN)
        foreach ([0.0, 100.0, 200.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $cert->id,
                'equipment_specification_id' => $specIn->id,
                'nominal_value' => $nom,
                'reading_value' => $nom + 0.02,
                'correction' => -0.02,
                'uncertainty' => 0.03,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // Add calibration points for Source (OUT)
        foreach ([0.0, 50.0, 100.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $cert->id,
                'equipment_specification_id' => $specOut->id,
                'nominal_value' => $nom,
                'reading_value' => $nom - 0.05,
                'correction' => 0.05,
                'uncertainty' => 0.06,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // 1. Verify getCertificateSpecifications derives directly from Equipment specifications
        $specs = $this->service->getCertificateSpecifications($cert);
        $this->assertCount(2, $specs);
        $this->assertEquals($specIn->id, $specs[0]->id);
        $this->assertEquals($specOut->id, $specs[1]->id);

        // 2. Verify generateFivePointGrid for Measurement (IN)
        $gridIn = $this->service->generateFivePointGrid($cert, null, null, null, $specIn->id);
        $this->assertTrue($gridIn['has_data']);
        $this->assertEquals('measurement', $gridIn['grandeur_type']);
        $this->assertEquals('success', $gridIn['grandeur_type_badge']);
        $this->assertEquals(__('Measurement (Sensor / In)'), $gridIn['grandeur_type_label']);
        $this->assertEquals('Temperature Measurement', $gridIn['parameter_name']);
        $this->assertEquals('°C', $gridIn['unit_symbol']);

        // 3. Verify generateFivePointGrid for Source (OUT)
        $gridOut = $this->service->generateFivePointGrid($cert, null, null, null, $specOut->id);
        $this->assertTrue($gridOut['has_data']);
        $this->assertEquals('source', $gridOut['grandeur_type']);
        $this->assertEquals('warning', $gridOut['grandeur_type_badge']);
        $this->assertEquals(__('Source / Generation (Out)'), $gridOut['grandeur_type_label']);
        $this->assertEquals('Temperature Generation', $gridOut['parameter_name']);
        $this->assertEquals('°C', $gridOut['unit_symbol']);

        // 4. Verify historical comparison separation:
        // Create an older certificate for the same equipment
        $olderCert = CalibrationCertificate::create([
            'reference' => 'CERT-MC500-2025',
            'equipment_id' => $multipurposeCalibrator->id,
            'status' => CalibrationCertificateStatus::Approved,
            'calibration_date' => Carbon::parse('2025-05-10'),
            'expiry_date' => Carbon::parse('2026-05-10'),
            'validity_period_months' => 12,
            'laboratory_name' => 'GMTM Calibration Center',
            'price' => 30000.00,
            'created_by' => $this->adminUser->id,
            'approved_by' => $this->adminUser->id,
            'approved_at' => now(),
        ]);

        foreach ([0.0, 100.0, 200.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $olderCert->id,
                'equipment_specification_id' => $specIn->id,
                'nominal_value' => $nom,
                'reading_value' => $nom + 0.01,
                'correction' => -0.01,
                'uncertainty' => 0.02,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        $comparisonIn = $this->service->generateMultiCertificateComparison($cert, [0.0, 50.0, 100.0, 150.0, 200.0], $specIn->id);
        $this->assertTrue($comparisonIn['has_data']);
        $this->assertCount(2, $comparisonIn['labels']); // 2025 and 2026 dates
        $this->assertStringContainsString('[IN]', $comparisonIn['datasets'][0]['label']);

        $comparisonOut = $this->service->generateMultiCertificateComparison($cert, [0.0, 25.0, 50.0, 75.0, 100.0], $specOut->id);
        $this->assertTrue($comparisonOut['has_data']);
        $this->assertCount(1, $comparisonOut['labels']); // Only 2026 has source points
        $this->assertStringContainsString('[OUT]', $comparisonOut['datasets'][0]['label']);

        // 5. Verify View rendering
        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.show', [
                'certificate' => $cert,
                'tab' => 'interpolation',
                'spec_id' => $specOut->id,
            ]));

        $response->assertOk();
        $response->assertSee('Temperature Measurement');
        $response->assertSee('Temperature Generation');
        $response->assertSee('fa-bolt');
        $response->assertSee('fa-sign-in-alt');
    }
}
