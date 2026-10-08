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
use App\Models\Equipment;
use App\Models\Grandeur;
use App\Models\Instrument;
use App\Models\InstrumentSpecification;
use App\Models\Mission;
use App\Models\Report;
use App\Models\Site;
use App\Models\TransmitterVerification;
use App\Models\TransmitterVerificationPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportInstrumentsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_report_instruments_index(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $response = $this->actingAs($user)->get('/en/metrology/reports/report-instruments');
        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-instruments.index');
        $response->assertViewHas('reportGroups');
        $response->assertViewHas('missions');
        $response->assertSee('name="mission_id"', false);
        $response->assertSee('Associated Mission');

        $filteredResponse = $this->actingAs($user)->get('/en/metrology/reports/report-instruments?mission_id=288');
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertViewIs('metrology.reports.report-instruments.index');
        $filteredResponse->assertSee(__('Reset'));

        $typeFiltered = $this->actingAs($user)->get('/en/metrology/reports/report-instruments?type=transmitter&status=conforme');
        $typeFiltered->assertStatus(200);
        $typeFiltered->assertViewIs('metrology.reports.report-instruments.index');

        $emptyTypeFiltered = $this->actingAs($user)->get('/en/metrology/reports/report-instruments?type=&status=');
        $emptyTypeFiltered->assertStatus(200);
        $emptyTypeFiltered->assertViewIs('metrology.reports.report-instruments.index');

        $proverResponse = $this->actingAs($user)->get('/en/metrology/reports/report-prover');
        $proverResponse->assertStatus(200);
        $proverResponse->assertViewIs('metrology.reports.report-Prover.index');

        $chromatoResponse = $this->actingAs($user)->get('/en/metrology/reports/report-chromatograph');
        $chromatoResponse->assertStatus(200);
        $chromatoResponse->assertViewIs('metrology.reports.report-chromatograph.index');
    }

    public function test_user_can_access_edit_measuring_instruments_report_and_update_it(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $report = Report::create([
            'report_number' => 'RPT-TEST-001',
            'category' => 'instruments',
            'status' => 'progress',
        ]);

        $editResponse = $this->actingAs($user)->get("/en/metrology/reports/{$report->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertViewIs('metrology.reports.report-instruments.edit');
        $editResponse->assertViewHas('report');
        $editResponse->assertSee('RPT-TEST-001');

        $updateResponse = $this->actingAs($user)->put("/en/metrology/reports/{$report->id}", [
            'report_number' => 'RPT-TEST-001-MOD',
            'status' => 'completed',
        ]);

        $updateResponse->assertRedirect(route('metrology.reports.show', $report->id));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'report_number' => 'RPT-TEST-001-MOD',
            'status' => 'completed',
        ]);
    }

    public function test_mission_details_endpoint_returns_instrument_image_url(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $site = Site::create([
            'full_name' => 'Gassi Touil Station',
            'short_name' => 'GTFT',
            'site_code' => 'GTFT-02',
        ]);

        $mission = Mission::create([
            'reference' => 'MS-TEST-002',
            'site_id' => $site->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        Instrument::create([
            'tag_number' => 'PT-1002',
            'serial_number' => 'SN-1002',
            'instrument_type' => 'transmitter',
            'status' => 'active',
            'site_id' => $site->id,
            'image_path' => 'instruments/test_transmitter.webp',
        ]);

        $response = $this->actingAs($user)->get("/en/metrology/reports/mission-details/{$mission->id}?category=instruments");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'site_name',
            'instruments' => [
                '*' => [
                    'id',
                    'tag_number',
                    'serial_number',
                    'instrument_type',
                    'range_min',
                    'range_max',
                    'unit',
                    'image_url',
                ],
            ],
            'calibrators',
        ]);

        $this->assertStringContainsString('instruments/test_transmitter.webp', (string) $response->json('instruments.0.image_url'));
    }

    public function test_user_can_access_report_instruments_show_view(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $site = Site::create([
            'name' => 'Station In Salah Test',
            'short_name' => 'INS-01',
            'site_code' => 'INS',
        ]);

        $mission = Mission::create([
            'reference' => 'MS-TEST-SHOW-01',
            'site_id' => $site->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $report = Report::create([
            'report_number' => 'RPT-2026-SHOW-01',
            'mission_id' => $mission->id,
            'category' => 'instruments',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('metrology.reports.show', $report->id));

        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-instruments.show');
        $response->assertViewHas('report');
        $response->assertViewHas('appareilsData');
        $response->assertViewHas('stats');
        $response->assertSee('RPT-2026-SHOW-01');
    }

    public function test_user_can_access_report_instruments_saisie_view(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $site = Site::create([
            'name' => 'Station In Salah Saisie Test',
            'short_name' => 'INS-SAISIE',
            'site_code' => 'INS-S',
        ]);

        $mission = Mission::create([
            'reference' => 'MS-TEST-SAISIE-01',
            'site_id' => $site->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $grandeur = Grandeur::firstOrCreate(
            ['name' => 'Pression'],
            ['symbol' => 'bar', 'type' => GrandeurType::Measurement]
        );

        $instrument = Instrument::create([
            'tag_number' => 'PT-SAISIE-01',
            'serial_number' => 'SN-SAISIE-01',
            'instrument_type' => 'transmitter',
            'fluid_type' => FluidType::Gas,
            'status' => 'active',
            'site_id' => $site->id,
        ]);

        InstrumentSpecification::create([
            'instrument_id' => $instrument->id,
            'grandeur_id' => $grandeur->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.5,
            'accuracy_type' => AccuracyType::Percentage,
        ]);

        $report = Report::create([
            'report_number' => 'RPT-2026-SAISIE-01',
            'mission_id' => $mission->id,
            'category' => 'instruments',
            'status' => 'progress',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('metrology.reports.saisie', [
            'report' => $report->id,
            'instrument' => $instrument->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('PT-SAISIE-01');
    }

    public function test_user_can_generate_detailed_and_summary_pdf_with_enum_casts(): void
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        $site = Site::create([
            'name' => 'Station In Salah PDF Test',
            'short_name' => 'INS-PDF',
            'site_code' => 'INS-P',
        ]);

        $mission = Mission::create([
            'reference' => 'MS-TEST-PDF-01',
            'site_id' => $site->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $grandeur = Grandeur::firstOrCreate(
            ['name' => 'Pression'],
            ['symbol' => 'bar', 'type' => GrandeurType::Measurement]
        );

        $instrument = Instrument::create([
            'tag_number' => 'PT-PDF-01',
            'serial_number' => 'SN-PDF-01',
            'instrument_type' => 'transmitter',
            'process_variable' => ProcessVariable::Pressure,
            'fluid_type' => FluidType::Gas,
            'measurement_type' => 'Relative',
            'status' => 'active',
            'site_id' => $site->id,
        ]);

        InstrumentSpecification::create([
            'instrument_id' => $instrument->id,
            'grandeur_id' => $grandeur->id,
            'range_min' => 0.0,
            'range_max' => 100.0,
            'accuracy_value' => 0.5,
            'accuracy_type' => AccuracyType::Percentage,
        ]);

        $report = Report::create([
            'report_number' => 'RPT-2026-PDF-01',
            'mission_id' => $mission->id,
            'category' => 'instruments',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $transmitterVerif = TransmitterVerification::create([
            'report_mission_id' => $report->id,
            'instrument_id' => $instrument->id,
            'verification_date' => now()->toDateString(),
            'overall_status' => true,
        ]);

        TransmitterVerificationPoint::create([
            'verification_id' => $transmitterVerif->id,
            'step_order' => 1,
            'cycle_phase' => 'Ascending',
            'applied_percentage' => 0.0,
            'reference_value' => 0.0,
            'measured_signal' => 4.0,
            'indicated_value' => 0.0,
            'emt_limit' => 0.5,
            'is_conforme' => true,
        ]);

        $equipment = Equipment::create([
            'internal_code' => 'CAL-01',
            'full_name' => 'Calibrateur Test Additel',
            'serial_number' => 'SN-CAL-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
        ]);

        CalibrationCertificate::create([
            'reference' => 'CERT-2026-001',
            'equipment_id' => $equipment->id,
            'calibration_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => CalibrationCertificateStatus::Approved,
        ]);

        $transmitterVerif->calibrators()->attach($equipment->id);

        $detailedResponse = $this->actingAs($user)->get(route('metrology.reports.pdf', $report->id));
        $detailedResponse->assertStatus(200);
        $detailedResponse->assertHeader('content-type', 'application/pdf');

        $summaryResponse = $this->actingAs($user)->get(route('metrology.reports.pdf.summary', $report->id));
        $summaryResponse->assertStatus(200);
        $summaryResponse->assertHeader('content-type', 'application/pdf');

        $report->load([
            'mission.site',
            'transmitterVerifications.instrument.specifications.grandeur',
            'transmitterVerifications.calibrators.calibrationCertificates',
            'transmitterVerifications.points',
            'probeVerifications.instrument.specifications.grandeur',
            'probeVerifications.calibrators.calibrationCertificates',
            'probeVerifications.points',
            'flowComputerVerifications.instrument.specifications.grandeur',
            'flowComputerVerifications.simulatedTransmitter.specifications.grandeur',
            'flowComputerVerifications.calibrators.calibrationCertificates',
            'flowComputerVerifications.points',
        ]);

        $renderedSummary = view('metrology.reports.pdf.instrument_verification_report_summary', compact('report'))->render();
        $this->assertStringContainsString('CERT-2026-001', $renderedSummary);
        $this->assertStringContainsString('data:image/png;base64,', $renderedSummary);
    }
}
