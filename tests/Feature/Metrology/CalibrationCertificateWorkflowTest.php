<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\CalibrationCertificateStatus;
use App\Enums\CalibrationPointStatus;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentPackage;
use App\Enums\EquipmentStatus;
use App\Enums\GrandeurType;
use App\Models\CalibrationCertificate;
use App\Models\CalibrationPoint;
use App\Models\Equipment;
use App\Models\EquipmentSpecification;
use App\Models\Grandeur;
use App\Models\User;
use App\Services\CalibrationCertificateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CalibrationCertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Equipment $equipment;

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
            'full_name' => 'Pressure Calibrator Additel ADT672',
            'short_name' => 'ADT672 100 bar',
            'internal_code' => 'LAB-WC-002',
            'serial_number' => 'SN-ADT672-007',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);
    }

    public function test_authenticated_user_can_view_certificates_index_with_statistics(): void
    {
        CalibrationCertificate::create([
            'reference' => 'CERT-2025-001',
            'certificate_type' => 'periodic',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'RE.EL SERVICES',
            'calibration_date' => Carbon::now()->subMonths(6),
            'expiry_date' => Carbon::now()->addMonths(6),
            'validity_period_months' => 12,
            'price' => 5000.0,
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates'));

        $response->assertOk()
            ->assertViewIs('metrology.certificates.index')
            ->assertSee('CERT-2025-001')
            ->assertSee('ADT672 100 bar')
            ->assertSee('RE.EL SERVICES');
    }

    public function test_user_can_filter_certificates_by_equipment_and_status(): void
    {
        $cert1 = CalibrationCertificate::create([
            'reference' => 'CERT-MATCH',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'status' => CalibrationCertificateStatus::Approved,
        ]);

        $otherEquipment = Equipment::create([
            'full_name' => 'Other Equipment',
            'short_name' => 'Other',
            'internal_code' => 'OTH-001',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $cert2 = CalibrationCertificate::create([
            'reference' => 'CERT-DIFFERENT',
            'equipment_id' => $otherEquipment->id,
            'calibration_date' => Carbon::now(),
            'status' => CalibrationCertificateStatus::Draft,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates', ['equipment_id' => $this->equipment->id]));

        $response->assertOk()
            ->assertSee('CERT-MATCH')
            ->assertDontSee('CERT-DIFFERENT');
    }

    public function test_user_can_create_certificate_with_points_and_pdf_document(): void
    {
        Storage::fake('public');

        $pdfFile = UploadedFile::fake()->create('official_calibration_cert.pdf', 500, 'application/pdf');

        $payload = [
            'reference' => 'CERT-NEW-2026',
            'certificate_type' => 'periodic',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'ONML Metrology Lab',
            'calibration_date' => '2026-09-01',
            'validity_period_months' => 12,
            'price' => 7500.50,
            'certificate_file' => $pdfFile,
            'environmental_conditions' => [
                'temperature_celsius' => 21.5,
                'humidity_percent' => 48.0,
                'atmospheric_pressure_hpa' => 1012.0,
            ],
            'remarks' => 'Calibrated in temperature controlled bath.',
            'points' => [
                ['nominal_value' => 0.0, 'correction' => 0.0, 'uncertainty' => 0.005],
                ['nominal_value' => 20.0, 'correction' => -0.01, 'uncertainty' => 0.008],
                ['nominal_value' => 50.0, 'correction' => 0.02, 'uncertainty' => 0.012],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.store'), $payload);

        $response->assertRedirect();

        $this->assertDatabaseHas('calibration_certificates', [
            'reference' => 'CERT-NEW-2026',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'ONML Metrology Lab',
            'price' => 7500.50,
            'status' => CalibrationCertificateStatus::Draft->value,
            'is_locked' => 0,
        ]);

        $cert = CalibrationCertificate::where('reference', 'CERT-NEW-2026')->firstOrFail();
        $this->assertNotNull($cert->certificate_path);
        $this->assertNotNull($cert->certificate_hash);
        $this->assertEquals(3, $cert->calibrationPoints()->count());

        // Ensure equipment shortcut was updated
        $this->equipment->refresh();
        $this->assertEquals($cert->certificate_path, $this->equipment->certificate_path);
    }

    public function test_user_can_view_certificate_details_and_curves(): void
    {
        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-SHOW-TEST',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'CETIM',
            'calibration_date' => '2026-05-10',
            'expiry_date' => '2027-05-10',
            'price' => 4500.0,
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert->id,
            'nominal_value' => 25.0,
            'correction' => -0.05,
            'uncertainty' => 0.015,
            'status' => CalibrationPointStatus::InTolerance,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.show', $cert));

        $response->assertOk()
            ->assertViewIs('metrology.certificates.show')
            ->assertSee('CERT-SHOW-TEST')
            ->assertSee('CETIM')
            ->assertSee('25')
            ->assertSee('-0.05')
            ->assertSee('interpolationViewer')
            ->assertSee('singleCurveChart')
            ->assertSee('comparisonCurveChart');
    }

    public function test_authorized_user_can_approve_and_lock_certificate(): void
    {
        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-DRAFT-LOCK',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Draft,
            'is_locked' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.approve', $cert));

        $response->assertRedirect();

        $cert->refresh();
        $this->assertTrue($cert->is_locked);
        $this->assertEquals(CalibrationCertificateStatus::Approved, $cert->status);
        $this->assertEquals($this->adminUser->id, $cert->approved_by);
        $this->assertEquals($this->adminUser->id, $cert->locked_by);
    }

    public function test_locked_certificate_cannot_be_modified_or_deleted(): void
    {
        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-IMMUTABLE',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        // Attempt edit view
        $editResponse = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.edit', $cert));
        $editResponse->assertRedirect(route('metrology.calibration-certificates.show', $cert));

        // Attempt destroy
        $this->expectException(\RuntimeException::class);
        $this->app->make(CalibrationCertificateService::class)->deleteCertificate($cert);
    }

    public function test_authorized_user_can_unlock_certificate_with_audit_reason(): void
    {
        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-LOCKED-FOR-UNLOCK',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.unlock', $cert), [
                'reason' => 'Auditor requested correction in laboratory certificate reference number.',
            ]);

        $response->assertRedirect();

        $cert->refresh();
        $this->assertFalse($cert->is_locked);
        $this->assertEquals(CalibrationCertificateStatus::UnderReview, $cert->status);
    }

    public function test_check_expiring_certificates_command_transitions_status(): void
    {
        // 1. Expired certificate (expiry date was 10 days ago)
        $expiredCert = CalibrationCertificate::create([
            'reference' => 'CERT-PAST-DUE',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now()->subYear()->subDays(10),
            'expiry_date' => Carbon::now()->subDays(10),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        // 2. Expiring soon certificate (expiry in 15 days)
        $expiringCert = CalibrationCertificate::create([
            'reference' => 'CERT-SOON',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now()->subYear()->addDays(15),
            'expiry_date' => Carbon::now()->addDays(15),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        $this->artisan('metrology:check-expiring-certificates', ['--days' => 30])
            ->assertSuccessful();

        $expiredCert->refresh();
        $expiringCert->refresh();

        $this->assertEquals(CalibrationCertificateStatus::Expired, $expiredCert->status);
        $this->assertEquals(CalibrationCertificateStatus::ExpiringSoon, $expiringCert->status);
    }

    public function test_download_certificate_with_slashes_in_reference_sanitizes_filename(): void
    {
        Storage::fake('public');

        $fakePdfPath = 'equipment/certificates/test-cert.pdf';
        Storage::disk('public')->put($fakePdfPath, '%PDF-1.4 test certificate content');

        $cert = CalibrationCertificate::create([
            'reference' => 'N I 010 / 2023',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Approved,
            'certificate_path' => $fakePdfPath,
            'is_locked' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.download', $cert));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename="N I 010 - 2023.pdf"');
    }

    public function test_show_certificate_displays_isolated_measurement_and_source_calibration_tables(): void
    {
        $measurementGrandeur = Grandeur::create([
            'name' => 'Pressure Sensor',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);

        $sourceGrandeur = Grandeur::create([
            'name' => 'Current Generator',
            'symbol' => 'mA',
            'type' => GrandeurType::Source,
        ]);

        $measurementSpec = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $measurementGrandeur->id,
            'range_min' => 0,
            'range_max' => 100,
            'accuracy_value' => 0.05,
        ]);

        $sourceSpec = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $sourceGrandeur->id,
            'range_min' => 4,
            'range_max' => 20,
            'accuracy_value' => 0.02,
        ]);

        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-DUAL-TABLE-001',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert->id,
            'equipment_specification_id' => $measurementSpec->id,
            'nominal_value' => 50.0,
            'correction' => 0.02,
            'uncertainty' => 0.01,
            'status' => CalibrationPointStatus::InTolerance,
        ]);

        CalibrationPoint::create([
            'calibration_certificate_id' => $cert->id,
            'equipment_specification_id' => $sourceSpec->id,
            'nominal_value' => 12.0,
            'correction' => -0.005,
            'uncertainty' => 0.002,
            'status' => CalibrationPointStatus::InTolerance,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/en/metrology/calibration-certificates/'.$cert->id);

        $response->assertOk();
        $response->assertSee('Measurement Standards &amp; Capabilities (Sensors / In)', false);
        $response->assertSee('Source Standards &amp; Capabilities (Generators / Out)', false);
        $response->assertSee('Measurement / In');
        $response->assertSee('Source / Out');
        $response->assertSee('Filter by Category');
        $response->assertSee('Pressure Sensor');
        $response->assertSee('Current Generator');
        $response->assertSee('0 → 100 bar', false);
        $response->assertSee('4 → 20 mA', false);
        $response->assertSee('50');
        $response->assertSee('12');

        // Also verify Arabic localization renders correctly (default locale without prefix)
        $arResponse = $this->withSession(['locale' => 'ar'])
            ->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.show', $cert));

        $arResponse->assertOk();
        $arResponse->assertSee('معايير وقدرات القياس (مستشعرات / إدخال)');
        $arResponse->assertSee('معايير وقدرات المصدر (مولدات / إخراج)');
        $arResponse->assertSee('قياس / إدخال');
        $arResponse->assertSee('مصدر / توليد');
        $arResponse->assertSee('نطاق القياس');
        $arResponse->assertSee('نطاق التوليد');
    }

    public function test_dual_capability_process_calibrator_isolates_measurement_and_source_temperature_points_without_duplicates(): void
    {
        $grandeurTempMesure = Grandeur::create([
            'name' => 'Température',
            'symbol' => '°C',
            'type' => GrandeurType::Measurement,
        ]);
        $grandeurTempSource = Grandeur::create([
            'name' => 'Température',
            'symbol' => '°C',
            'type' => GrandeurType::Source,
        ]);

        $specMesure = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurTempMesure->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        $specSource = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurTempSource->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        $cert = CalibrationCertificate::create([
            'reference' => 'CERT-DUAL-TEMP-001',
            'equipment_id' => $this->equipment->id,
            'calibration_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addYear(),
            'status' => CalibrationCertificateStatus::Approved,
            'is_locked' => true,
        ]);

        // 3 points in Measurement (e.g. 0, 50, 100)
        foreach ([0.0, 50.0, 100.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $cert->id,
                'equipment_specification_id' => $specMesure->id,
                'nominal_value' => $nom,
                'correction' => 0.01,
                'uncertainty' => 0.02,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        // 3 points in Source (e.g. 0, 50, 100)
        foreach ([0.0, 50.0, 100.0] as $nom) {
            CalibrationPoint::create([
                'calibration_certificate_id' => $cert->id,
                'equipment_specification_id' => $specSource->id,
                'nominal_value' => $nom,
                'correction' => -0.01,
                'uncertainty' => 0.02,
                'status' => CalibrationPointStatus::InTolerance,
            ]);
        }

        $this->assertEquals(6, $cert->calibrationPoints()->count());
        $this->assertEquals(3, $cert->calibrationPoints()->where('equipment_specification_id', $specMesure->id)->count());
        $this->assertEquals(3, $cert->calibrationPoints()->where('equipment_specification_id', $specSource->id)->count());

        $response = $this->actingAs($this->adminUser)
            ->get('/en/metrology/calibration-certificates/'.$cert->id);

        $response->assertOk();
        $response->assertSee('Measurement Standards &amp; Capabilities (Sensors / In)', false);
        $response->assertSee('Source Standards &amp; Capabilities (Generators / Out)', false);
        $response->assertSee('View Curve');
        $response->assertSee('spec_id='.$specSource->id);
        $response->assertSee('spec_id='.$specMesure->id);
    }

    public function test_create_and_edit_multi_standard_calibration_certificate_with_isolated_points(): void
    {
        Storage::fake('public');

        $grandeurMesure = Grandeur::create([
            'name' => 'Voltage In',
            'symbol' => 'V',
            'type' => GrandeurType::Measurement,
        ]);

        $grandeurSource = Grandeur::create([
            'name' => 'Voltage Out',
            'symbol' => 'V',
            'type' => GrandeurType::Source,
        ]);

        $specMesure = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurMesure->id,
            'range_min' => 0.0,
            'range_max' => 10.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        $specSource = EquipmentSpecification::create([
            'equipment_id' => $this->equipment->id,
            'grandeur_id' => $grandeurSource->id,
            'range_min' => 0.0,
            'range_max' => 10.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        // 1. Visit Create page and verify multi-standard elements
        $createResponse = $this->actingAs($this->adminUser)
            ->get('/en/metrology/calibration-certificates/create');

        $createResponse->assertOk()
            ->assertViewHas('equipmentsData')
            ->assertSee('Multi-Standard Capabilities &amp; Calibration Points', false);

        // 2. Submit certificate with points for both Measurement and Source standards
        $payload = [
            'reference' => 'CERT-MULTI-STD-2026',
            'certificate_type' => 'periodic',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'GMTM Lab',
            'calibration_date' => '2026-09-15',
            'validity_period_months' => 12,
            'price' => 5000.0,
            'points' => [
                [
                    'equipment_specification_id' => $specMesure->id,
                    'nominal_value' => 2.5,
                    'correction' => 0.001,
                    'uncertainty' => 0.002,
                ],
                [
                    'equipment_specification_id' => $specMesure->id,
                    'nominal_value' => 5.0,
                    'correction' => 0.002,
                    'uncertainty' => 0.003,
                ],
                [
                    'equipment_specification_id' => $specSource->id,
                    'nominal_value' => 2.5,
                    'correction' => -0.001,
                    'uncertainty' => 0.002,
                ],
                [
                    'equipment_specification_id' => $specSource->id,
                    'nominal_value' => 5.0,
                    'correction' => -0.002,
                    'uncertainty' => 0.003,
                ],
            ],
        ];

        $postResponse = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.store'), $payload);

        $postResponse->assertRedirect();

        $cert = CalibrationCertificate::where('reference', 'CERT-MULTI-STD-2026')->firstOrFail();
        $this->assertEquals(4, $cert->calibrationPoints()->count());
        $this->assertEquals(2, $cert->calibrationPoints()->where('equipment_specification_id', $specMesure->id)->count());
        $this->assertEquals(2, $cert->calibrationPoints()->where('equipment_specification_id', $specSource->id)->count());

        // 3. Visit Edit page and verify multi-standard points are preserved and view renders
        $editResponse = $this->actingAs($this->adminUser)
            ->get('/en/metrology/calibration-certificates/'.$cert->id.'/edit');

        $editResponse->assertOk()
            ->assertViewHas('equipmentsData')
            ->assertSee('CERT-MULTI-STD-2026')
            ->assertSee('Multi-Standard Capabilities &amp; Calibration Points', false);
    }

    public function test_certificates_module_only_fetches_and_accepts_calibration_eligible_equipment(): void
    {
        // Create vehicle and work tool that do not accept calibration
        $vehicle = Equipment::create([
            'full_name' => 'Toyota Hilux 4x4',
            'short_name' => 'Hilux Service 01',
            'internal_code' => 'VEH-001',
            'category' => EquipmentCategory::Vehicle,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        $workTool = Equipment::create([
            'full_name' => 'Set of Screwdrivers Facom',
            'short_name' => 'Tournevis Facom',
            'internal_code' => 'OUT-001',
            'category' => EquipmentCategory::WorkTool,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        // 1. Index page: $equipments passed to view must contain measuring instrument but NOT vehicle or work tool
        $indexResponse = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates'));

        $indexResponse->assertOk();
        $indexEquipments = $indexResponse->viewData('equipments');
        $this->assertTrue($indexEquipments->contains('id', $this->equipment->id));
        $this->assertFalse($indexEquipments->contains('id', $vehicle->id));
        $this->assertFalse($indexEquipments->contains('id', $workTool->id));

        // 2. Create page: $equipments passed to view must NOT contain vehicle or work tool
        $createResponse = $this->actingAs($this->adminUser)
            ->get(route('metrology.calibration-certificates.create'));

        $createResponse->assertOk();
        $createEquipments = $createResponse->viewData('equipments');
        $this->assertTrue($createEquipments->contains('id', $this->equipment->id));
        $this->assertFalse($createEquipments->contains('id', $vehicle->id));
        $this->assertFalse($createEquipments->contains('id', $workTool->id));

        // 3. Store attempt with vehicle ID must be rejected by validation
        $vehiclePayload = [
            'reference' => 'CERT-INVALID-VEHICLE',
            'certificate_type' => 'periodic',
            'equipment_id' => $vehicle->id,
            'laboratory_name' => 'RE.EL SERVICES',
            'calibration_date' => now()->format('Y-m-d'),
            'validity_period_months' => 12,
        ];

        $vehicleResponse = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.store'), $vehiclePayload);

        $vehicleResponse->assertSessionHasErrors(['equipment_id']);

        // 4. Store attempt with work tool ID must be rejected by validation
        $toolPayload = [
            'reference' => 'CERT-INVALID-TOOL',
            'certificate_type' => 'periodic',
            'equipment_id' => $workTool->id,
            'laboratory_name' => 'RE.EL SERVICES',
            'calibration_date' => now()->format('Y-m-d'),
            'validity_period_months' => 12,
        ];

        $toolResponse = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.store'), $toolPayload);

        $toolResponse->assertSessionHasErrors(['equipment_id']);

        // 5. Store attempt with valid calibration equipment must succeed
        $validPayload = [
            'reference' => 'CERT-VALID-CALIB-2026',
            'certificate_type' => 'periodic',
            'equipment_id' => $this->equipment->id,
            'laboratory_name' => 'RE.EL SERVICES',
            'calibration_date' => now()->format('Y-m-d'),
            'validity_period_months' => 12,
        ];

        $validResponse = $this->actingAs($this->adminUser)
            ->post(route('metrology.calibration-certificates.store'), $validPayload);

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('calibration_certificates', [
            'reference' => 'CERT-VALID-CALIB-2026',
            'equipment_id' => $this->equipment->id,
        ]);
    }
}
