<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\GrandeurType;
use App\Enums\InstrumentStatus;
use App\Enums\InstrumentType;
use App\Enums\ProcessVariable;
use App\Models\Grandeur;
use App\Models\Instrument;
use App\Models\ProverSpecification;
use App\Models\Report;
use App\Models\Site;
use App\Models\StandardGaugeSpecification;
use App\Models\TransmitterVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeasuringInstrumentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Site $site;

    protected Grandeur $grandeurPressure;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($superRole);

        $this->site = Site::create([
            'full_name' => 'Gassi Touil Field Treatment Station',
            'short_name' => 'G-TFT',
            'site_code' => 'GTFT-01',
        ]);

        $this->grandeurPressure = Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);
    }

    public function test_guest_cannot_view_instruments(): void
    {
        $response = $this->get(route('metrology.instruments'));
        $response->assertRedirect(route('login'));
    }

    public function test_can_view_instruments_index_page(): void
    {
        $instrument = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-4240A',
            'serial_number' => 'SN-8159216',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments'));

        $response->assertOk();
        $response->assertSee('PT-4240A');
        $response->assertSee('SN-8159216');
        $response->assertSee('G-TFT');
        $response->assertSee(route('metrology.instruments.edit', $instrument));
        $response->assertSee(':action="deleteActionUrl"', false);
    }

    public function test_can_view_instrument_create_hub_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments.create'));

        $response->assertRedirect(route('metrology.instruments'));
    }

    public function test_can_view_instrument_create_type_pages(): void
    {
        $types = ['transmitter', 'flow-computer', 'probe', 'chromatograph', 'standard-gauge', 'prover'];

        foreach ($types as $type) {
            $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments.create.type', $type));

            $response->assertOk();
            $response->assertSee('tag_number');
            $response->assertSee('serial_number');
        }
    }

    public function test_can_view_instrument_edit_page(): void
    {
        $types = [
            InstrumentType::Transmitter,
            InstrumentType::FlowComputer,
            InstrumentType::Probe,
            InstrumentType::Chromatograph,
            InstrumentType::StandardGauge,
            InstrumentType::Prover,
        ];

        foreach ($types as $type) {
            $instrument = Instrument::create([
                'site_id' => $this->site->id,
                'tag_number' => 'TAG-'.$type->value,
                'serial_number' => 'SN-'.$type->value,
                'instrument_type' => $type,
                'status' => InstrumentStatus::Active,
            ]);

            $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments.edit', $instrument));

            $response->assertOk();
            $response->assertSee('TAG-'.$type->value);
            $response->assertSee('SN-'.$type->value);
        }
    }

    public function test_can_view_instrument_show_page(): void
    {
        $instrument = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-4240A',
            'serial_number' => 'SN-8159216',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments.show', $instrument));

        $response->assertOk();
        $response->assertSee('PT-4240A');
        $response->assertSee('SN-8159216');
        $response->assertSee('G-TFT');
    }

    public function test_can_create_instrument_with_specifications(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'tag_number' => 'TT-901',
            'serial_number' => 'SN-TT901',
            'instrument_type' => 'transmitter',
            'process_variable' => 'temperature',
            'fluid_type' => 'gas',
            'status' => 'active',
            'params' => [
                $this->grandeurPressure->id => [
                    'selected' => '1',
                    'min' => '0',
                    'max' => '150',
                    'acc' => '0.05',
                    'acc_type' => '%',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('metrology.instruments.store'), $payload);

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertDatabaseHas('instruments', [
            'tag_number' => 'TT-901',
            'serial_number' => 'SN-TT901',
            'site_id' => $this->site->id,
        ]);

        $instrument = Instrument::where('serial_number', 'SN-TT901')->first();
        $this->assertNotNull($instrument);
        $this->assertCount(1, $instrument->specifications);
    }

    public function test_can_update_instrument(): void
    {
        $instrument = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-OLD',
            'serial_number' => 'SN-OLD',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('metrology.instruments.update', $instrument), [
            'edit_id' => $instrument->id,
            'site_id' => $this->site->id,
            'tag_number' => 'PT-NEW',
            'serial_number' => 'SN-NEW',
            'instrument_type' => 'transmitter',
            'process_variable' => 'pressure',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertDatabaseHas('instruments', [
            'id' => $instrument->id,
            'tag_number' => 'PT-NEW',
            'serial_number' => 'SN-NEW',
            'status' => 'inactive',
        ]);
    }

    public function test_can_update_instrument_keeping_same_serial_number(): void
    {
        $instrument = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-KEEP',
            'serial_number' => 'SN-KEEP-123',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('metrology.instruments.update', $instrument), [
            'edit_id' => $instrument->id,
            'site_id' => $this->site->id,
            'tag_number' => 'PT-KEEP-MODIFIED',
            'serial_number' => 'SN-KEEP-123',
            'instrument_type' => 'transmitter',
            'process_variable' => 'pressure',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertDatabaseHas('instruments', [
            'id' => $instrument->id,
            'tag_number' => 'PT-KEEP-MODIFIED',
            'serial_number' => 'SN-KEEP-123',
        ]);
    }

    public function test_can_delete_instrument(): void
    {
        $instrument = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-DEL',
            'serial_number' => 'SN-DEL',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('metrology.instruments.destroy', $instrument));

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertSoftDeleted('instruments', [
            'id' => $instrument->id,
        ]);
    }

    public function test_can_delete_standard_gauge_and_its_specifications_are_cleaned(): void
    {
        $gauge = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'JAUGE-DEL-01',
            'serial_number' => 'SN-JAUGE-DEL-01',
            'instrument_type' => InstrumentType::StandardGauge,
            'status' => InstrumentStatus::Active,
        ]);

        StandardGaugeSpecification::create([
            'instrument_id' => $gauge->id,
            'nominal_capacity_liters' => 500.0,
            'neck_scale_sensitivity' => 0.05,
        ]);

        $this->assertDatabaseHas('standard_gauge_specifications', ['instrument_id' => $gauge->id]);

        $response = $this->actingAs($this->adminUser)->delete(route('metrology.instruments.destroy', $gauge));

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertSoftDeleted('instruments', ['id' => $gauge->id]);
        $this->assertDatabaseMissing('standard_gauge_specifications', ['instrument_id' => $gauge->id]);
    }

    public function test_can_delete_prover_and_its_specifications_are_cleaned(): void
    {
        $prover = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PROVER-DEL-01',
            'serial_number' => 'SN-PROVER-DEL-01',
            'instrument_type' => InstrumentType::Prover,
            'status' => InstrumentStatus::Active,
        ]);

        ProverSpecification::create([
            'instrument_id' => $prover->id,
            'type' => 'bidirectional_pipe',
            'nominal_base_volume' => 1500.0,
        ]);

        $this->assertDatabaseHas('prover_specifications', ['instrument_id' => $prover->id]);

        $response = $this->actingAs($this->adminUser)->delete(route('metrology.instruments.destroy', $prover));

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertSoftDeleted('instruments', ['id' => $prover->id]);
        $this->assertDatabaseMissing('prover_specifications', ['instrument_id' => $prover->id]);
    }

    public function test_can_delete_flow_computer_and_linked_transmitters_are_detached(): void
    {
        $transmitter = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'TX-DEL-01',
            'serial_number' => 'SN-TX-DEL-01',
            'instrument_type' => InstrumentType::Transmitter,
            'status' => InstrumentStatus::Active,
        ]);

        $flowComputer = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'FC-DEL-01',
            'serial_number' => 'SN-FC-DEL-01',
            'instrument_type' => InstrumentType::FlowComputer,
            'status' => InstrumentStatus::Active,
        ]);

        $flowComputer->linkedTransmitters()->attach($transmitter->id, ['channel_number' => 'Ch1']);

        $this->assertDatabaseHas('flow_computer_transmitter', [
            'flow_computer_id' => $flowComputer->id,
            'transmitter_id' => $transmitter->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('metrology.instruments.destroy', $flowComputer));

        $response->assertRedirect(route('metrology.instruments'));
        $this->assertSoftDeleted('instruments', ['id' => $flowComputer->id]);
        $this->assertDatabaseMissing('flow_computer_transmitter', [
            'flow_computer_id' => $flowComputer->id,
        ]);
        // Transmitter itself is still active
        $this->assertDatabaseHas('instruments', ['id' => $transmitter->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_instrument_with_existing_verification_reports(): void
    {
        $transmitter = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PT-LOCKED-01',
            'serial_number' => 'SN-LOCKED-01',
            'instrument_type' => InstrumentType::Transmitter,
            'process_variable' => ProcessVariable::Pressure,
            'status' => InstrumentStatus::Active,
        ]);

        $report = Report::create([
            'report_number' => 'REP-TEST-001',
            'status' => 'progress',
        ]);

        TransmitterVerification::create([
            'instrument_id' => $transmitter->id,
            'report_mission_id' => $report->id,
            'verification_date' => now(),
            'overall_status' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('metrology.instruments.destroy', $transmitter));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('instruments', [
            'id' => $transmitter->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_filter_instruments_by_gauges_and_provers_combined(): void
    {
        $gauge = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'GAUGE-01',
            'serial_number' => 'SN-GAUGE-01',
            'instrument_type' => InstrumentType::StandardGauge,
            'status' => InstrumentStatus::Active,
        ]);

        $prover = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'PROVER-01',
            'serial_number' => 'SN-PROVER-01',
            'instrument_type' => InstrumentType::Prover,
            'status' => InstrumentStatus::Active,
        ]);

        $transmitter = Instrument::create([
            'site_id' => $this->site->id,
            'tag_number' => 'TX-01',
            'serial_number' => 'SN-TX-01',
            'instrument_type' => InstrumentType::Transmitter,
            'status' => InstrumentStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.instruments', ['instrument_type' => 'gauges_and_provers']));

        $response->assertOk();
        $response->assertSee('GAUGE-01');
        $response->assertSee('PROVER-01');
        $response->assertDontSee('TX-01');
    }
}
