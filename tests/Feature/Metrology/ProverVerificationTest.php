<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProverType;
use App\Models\Instrument;
use App\Models\ProverSpecification;
use App\Models\ProverVerification;
use App\Models\ProverVerificationRun;
use App\Models\Site;
use App\Models\StandardGaugeSpecification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProverVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $this->artisan('permissions:sync-tables');
        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($superRole);

        return $user;
    }

    private function createProverAndGauge(Site $site): array
    {
        $prover = Instrument::create([
            'site_id' => $site->id,
            'tag_number' => 'CP-TEST-001',
            'serial_number' => 'SN-CP-001',
            'instrument_type' => InstrumentType::Prover,
            'status' => InstrumentStatus::Active,
        ]);

        ProverSpecification::create([
            'instrument_id' => $prover->id,
            'type' => ProverType::CompactSvp,
            'inner_diameter' => 300.0,
            'wall_thickness' => 10.0,
            'nominal_base_volume' => 120.0,
            'material' => 'Stainless Steel 304',
            'modulus_elasticity' => 195000.0,
            'cubical_expansion' => 0.0000477,
        ]);

        $gauge = Instrument::create([
            'site_id' => $site->id,
            'tag_number' => 'JG-TEST-001',
            'serial_number' => 'SN-JG-001',
            'instrument_type' => InstrumentType::StandardGauge,
            'status' => InstrumentStatus::Active,
        ]);

        StandardGaugeSpecification::create([
            'instrument_id' => $gauge->id,
            'nominal_capacity_liters' => 120.0,
            'neck_scale_sensitivity' => 1.0,
            'cubical_expansion' => 0.0000335,
            'calibration_certificate_number' => 'CERT-TEST-2026',
        ]);

        return [$prover, $gauge];
    }

    public function test_can_view_prover_verification_create_page(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create(['full_name' => 'Site Prover Lab']);
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $response = $this->actingAs($user)->get('/en/metrology/reports/report-prover/create');

        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-prover.create');
        $response->assertSee('CP-TEST-001');
        $response->assertSee('JG-TEST-001');
        $response->assertSee('Site Prover Lab');
    }

    public function test_can_calculate_preview_via_ajax(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $payload = [
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'runs' => [
                [
                    'run_number' => 1,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.75,
                            'shaft_temperature' => 28.5,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 2,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.9,
                            'shaft_temperature' => 28.9,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 3,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.9,
                            'prover_temperature' => 30.0,
                            'shaft_temperature' => 29.3,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson('/en/metrology/reports/report-prover/calculate-preview', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'base_prover_volume',
                'repeatability_percent',
                'is_conforme',
                'max_run_volume',
                'min_run_volume',
                'runs',
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_conforme' => true,
            ],
        ]);
    }

    public function test_can_store_prover_verification_session(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $payload = [
            'site_id' => $site->id,
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'remarks' => 'Verification test run under ISO 7278 conditions',
            'runs' => [
                [
                    'run_number' => 1,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.75,
                            'shaft_temperature' => 28.5,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 2,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.9,
                            'shaft_temperature' => 28.9,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 3,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.9,
                            'prover_temperature' => 30.0,
                            'shaft_temperature' => 29.3,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/en/metrology/reports/report-prover', $payload);

        $this->assertDatabaseHas('prover_verifications', [
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'pressure_unit' => 'bar',
            'is_conforme' => 1,
        ]);

        $created = ProverVerification::where('prover_id', $prover->id)->first();
        $this->assertNotNull($created);
        $this->assertCount(3, $created->runs);

        $response->assertRedirect(route('metrology.reports.report-prover.show', $created));
    }

    public function test_can_view_prover_verification_show_page(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $verif = ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/CERT-0099',
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'base_prover_volume' => 120.23528,
            'repeatability_percent' => 0.0044,
            'is_conforme' => true,
        ]);

        ProverVerificationRun::create([
            'prover_verification_id' => $verif->id,
            'run_number' => 1,
            'fill_number' => 1,
            'indicated_volume' => 120.187,
            'gauge_temperature' => 29.8,
            'prover_temperature' => 29.75,
            'shaft_temperature' => 28.5,
            'prover_pressure' => 0.75,
            'c_tdw' => 0.99998493,
            'c_tsm' => 1.00076664,
            'c_tsp' => 1.00033805,
            'c_psp' => 1.00000534,
            'c_plp' => 1.00003480,
            'corrected_volume' => 120.23186,
        ]);

        $response = $this->actingAs($user)->get(route('metrology.reports.report-prover.show', $verif));

        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-prover.show');
        $response->assertSee('FE/WDP/CERT-0099');
        $response->assertSee('120.23528');
        $response->assertSee('0.0044%');
        $response->assertSee('CP-TEST-001');
        $response->assertSee('JG-TEST-001');
    }

    public function test_can_generate_prover_pdfs(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $verif = ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/CERT-PDF',
            'base_prover_volume' => 120.23528,
            'repeatability_percent' => 0.0044,
            'is_conforme' => true,
        ]);

        $pdfDetailed = $this->actingAs($user)->get(route('metrology.reports.report-prover.pdf', $verif));
        $pdfDetailed->assertStatus(200);
        $pdfDetailed->assertHeader('content-type', 'application/pdf');

        $pdfSummary = $this->actingAs($user)->get(route('metrology.reports.report-prover.pdf-not-emt', $verif));
        $pdfSummary->assertStatus(200);
        $pdfSummary->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_delete_prover_verification_session(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $verif = ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/TO-DELETE',
            'base_prover_volume' => 120.23528,
            'repeatability_percent' => 0.0044,
            'is_conforme' => true,
        ]);

        ProverVerificationRun::create([
            'prover_verification_id' => $verif->id,
            'run_number' => 1,
            'fill_number' => 1,
            'indicated_volume' => 120.187,
            'gauge_temperature' => 29.8,
            'prover_temperature' => 29.75,
            'prover_pressure' => 0.75,
            'corrected_volume' => 120.23186,
        ]);

        $response = $this->actingAs($user)->delete(route('metrology.reports.report-prover.destroy', $verif));

        $response->assertRedirect(route('metrology.reports.report-prover.index'));
        $this->assertSoftDeleted('prover_verifications', ['id' => $verif->id]);
        $this->assertSoftDeleted('prover_verification_runs', ['prover_verification_id' => $verif->id]);
    }

    public function test_can_view_prover_verification_edit_page(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $verif = ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/EDIT-001',
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'base_prover_volume' => 120.23528,
            'repeatability_percent' => 0.0044,
            'is_conforme' => true,
        ]);

        $response = $this->actingAs($user)->get(route('metrology.reports.report-prover.edit', $verif));

        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-prover.edit');
        $response->assertSee('FE/WDP/EDIT-001');
    }

    public function test_can_update_prover_verification_session(): void
    {
        $user = $this->createAdminUser();
        $site = Site::factory()->create();
        [$prover, $gauge] = $this->createProverAndGauge($site);

        $verif = ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/BEFORE-UPDATE',
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'base_prover_volume' => 120.23528,
            'repeatability_percent' => 0.0044,
            'is_conforme' => true,
        ]);

        $payload = [
            'site_id' => $site->id,
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => '2026-10-09',
            'reference_number' => 'FE/WDP/AFTER-UPDATE',
            'reference_temperature' => 15.0,
            'pressure_unit' => 'bar',
            'remarks' => 'Updated remarks',
            'runs' => [
                [
                    'run_number' => 1,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.75,
                            'shaft_temperature' => 28.5,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 2,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.8,
                            'prover_temperature' => 29.9,
                            'shaft_temperature' => 28.9,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
                [
                    'run_number' => 3,
                    'fills' => [
                        [
                            'fill_number' => 1,
                            'indicated_volume' => 120.187,
                            'gauge_temperature' => 29.9,
                            'prover_temperature' => 30.0,
                            'shaft_temperature' => 29.3,
                            'prover_pressure' => 0.75,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->put(route('metrology.reports.report-prover.update', $verif), $payload);

        $response->assertRedirect(route('metrology.reports.report-prover.show', $verif));
        $this->assertDatabaseHas('prover_verifications', [
            'id' => $verif->id,
            'reference_number' => 'FE/WDP/AFTER-UPDATE',
            'remarks' => 'Updated remarks',
        ]);
    }
}
