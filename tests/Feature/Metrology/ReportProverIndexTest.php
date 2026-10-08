<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProverType;
use App\Models\Instrument;
use App\Models\ProverSpecification;
use App\Models\ProverVerification;
use App\Models\Site;
use App\Models\StandardGaugeSpecification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportProverIndexTest extends TestCase
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

    public function test_authenticated_user_can_access_report_prover_index(): void
    {
        $user = $this->createAdminUser();

        $site = Site::factory()->create([
            'full_name' => 'Hassi Messaoud Production Site',
            'short_name' => 'HMD-01',
            'site_code' => 'HMD',
        ]);

        $prover = Instrument::create([
            'site_id' => $site->id,
            'tag_number' => 'PRV-HMD-001',
            'serial_number' => 'SN-PRV-9988',
            'instrument_type' => InstrumentType::Prover,
            'status' => InstrumentStatus::Active,
        ]);

        ProverSpecification::create([
            'instrument_id' => $prover->id,
            'type' => ProverType::BidirectionalPipe,
            'inner_diameter' => 406.4,
            'wall_thickness' => 12.7,
            'nominal_base_volume' => 399.85,
            'material' => 'Carbon Steel A106 Gr. B',
        ]);

        $gauge = Instrument::create([
            'site_id' => $site->id,
            'tag_number' => 'JAUGE-HMD-500',
            'serial_number' => 'SN-JG-1122',
            'instrument_type' => InstrumentType::StandardGauge,
            'status' => InstrumentStatus::Active,
        ]);

        StandardGaugeSpecification::create([
            'instrument_id' => $gauge->id,
            'nominal_capacity_liters' => 500.0,
            'neck_scale_sensitivity' => 1.5,
            'calibration_certificate_number' => 'CERT-OAM-2026-089',
        ]);

        ProverVerification::create([
            'prover_id' => $prover->id,
            'jauge_id' => $gauge->id,
            'calibration_date' => now()->toDateString(),
            'reference_number' => 'FE/WDP/PRV-001',
            'base_prover_volume' => 399.874,
            'repeatability_percent' => 0.0125,
            'is_conforme' => true,
        ]);

        $response = $this->actingAs($user)->get('/en/metrology/reports/report-prover');

        $response->assertStatus(200);
        $response->assertViewIs('metrology.reports.report-Prover.index');
        $response->assertViewHas(['provers', 'sites', 'stats', 'filters']);
        $response->assertSee('PRV-HMD-001');
        $response->assertSee('JAUGE-HMD-500');
        $response->assertSee('HMD-01');
        $response->assertSee('399.85 L');
        $response->assertSee('500.00 L');
        $response->assertSee('CERT-OAM-2026-089');
    }

    public function test_filtering_by_search_type_and_site(): void
    {
        $user = $this->createAdminUser();

        $siteA = Site::factory()->create(['full_name' => 'Site Alpha', 'short_name' => 'ALP']);
        $siteB = Site::factory()->create(['full_name' => 'Site Beta', 'short_name' => 'BET']);

        $proverA = Instrument::create([
            'site_id' => $siteA->id,
            'tag_number' => 'PRV-ALPHA-10',
            'serial_number' => 'SN-ALP-10',
            'instrument_type' => InstrumentType::Prover,
            'status' => InstrumentStatus::Active,
        ]);

        $gaugeB = Instrument::create([
            'site_id' => $siteB->id,
            'tag_number' => 'JAUGE-BETA-20',
            'serial_number' => 'SN-BET-20',
            'instrument_type' => InstrumentType::StandardGauge,
            'status' => InstrumentStatus::Active,
        ]);

        // 1. Search filter
        $searchResponse = $this->actingAs($user)->get('/en/metrology/reports/report-prover?search=ALPHA');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('PRV-ALPHA-10');
        $searchResponse->assertDontSee('JAUGE-BETA-20');

        // 2. Type filter
        $typeResponse = $this->actingAs($user)->get('/en/metrology/reports/report-prover?type=standard_gauge');
        $typeResponse->assertStatus(200);
        $typeResponse->assertSee('JAUGE-BETA-20');
        $typeResponse->assertDontSee('PRV-ALPHA-10');

        // 3. Site filter
        $siteResponse = $this->actingAs($user)->get('/en/metrology/reports/report-prover?site_id='.$siteA->id);
        $siteResponse->assertStatus(200);
        $siteResponse->assertSee('PRV-ALPHA-10');
        $siteResponse->assertDontSee('JAUGE-BETA-20');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/en/metrology/reports/report-prover');
        $response->assertRedirect('/login');
    }
}
