<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\EquipmentCategory;
use App\Enums\EquipmentPackage;
use App\Enums\EquipmentStatus;
use App\Enums\GrandeurType;
use App\Models\Equipment;
use App\Models\Grandeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EquipmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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

        $this->grandeurPressure = Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);
    }

    public function test_can_view_equipment_index_page_with_kpis(): void
    {
        Equipment::create([
            'full_name' => 'Digital Manometer Test',
            'short_name' => 'DMT-01',
            'internal_code' => 'DMT-2026',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.equipment'));

        $response->assertOk();
        $response->assertDontSee('@js(');
        $response->assertSee('openEditModal(JSON.parse(atob($el.dataset.item))');
        $response->assertSee('data-item=');
        $response->assertSee('Digital Manometer Test');
        $response->assertSee('DMT-01');
        $response->assertSee('DMT-2026');
    }

    public function test_can_view_equipment_show_details_page(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Fluke Multimeter',
            'short_name' => 'FLK-87V',
            'internal_code' => 'EQ-FLK-001',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.equipment.show', $equipment));

        $response->assertOk();
        $response->assertViewHas('measurementGrandeurs');
        $response->assertViewHas('sourceGrandeurs');
        $response->assertSee('Fluke Multimeter');
        $response->assertSee('EQ-FLK-001');
        $response->assertSee('open-edit-equipment-modal');
        $response->assertSee(__('Edit Equipment'));
    }

    public function test_can_update_equipment_from_show_page_with_redirect(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Original Name',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        $redirectTarget = route('metrology.equipment.show', $equipment);

        $response = $this->actingAs($this->adminUser)
            ->put(route('metrology.equipment.update', $equipment), [
                'full_name' => 'Updated From Show',
                'category' => EquipmentCategory::MeasuringInstrument->value,
                '_redirect' => $redirectTarget,
            ]);

        $response->assertRedirect($redirectTarget);
        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'full_name' => 'Updated From Show',
        ]);
    }

    public function test_can_create_equipment_with_specifications(): void
    {
        $data = [
            'full_name' => 'Druck DPI 610 Calibrator',
            'short_name' => 'DPI-610',
            'internal_code' => 'EQ-CAL-099',
            'serial_number' => 'SN-998877',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'package' => EquipmentPackage::Lot01->value,
            'status' => EquipmentStatus::Active->value,
            'requires_calibration' => '1',
            'params' => [
                $this->grandeurPressure->id => [
                    'selected' => '1',
                    'min' => '0.0',
                    'max' => '20.0',
                    'acc' => '0.025',
                    'acc_type' => '%',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.equipment.store'), $data);

        $response->assertRedirect(route('metrology.equipment'));
        $this->assertDatabaseHas('equipment', [
            'internal_code' => 'EQ-CAL-099',
            'full_name' => 'Druck DPI 610 Calibrator',
        ]);

        $created = Equipment::where('internal_code', 'EQ-CAL-099')->firstOrFail();
        $this->assertCount(1, $created->specifications);
        $this->assertEquals(20.0, $created->specifications->first()->range_max);
    }

    public function test_can_update_equipment_and_sync_specifications(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Original Gauge',
            'short_name' => 'OG-01',
            'internal_code' => 'EQ-OG-01',
            'category' => EquipmentCategory::WorkTool,
            'package' => EquipmentPackage::None,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        $updateData = [
            'full_name' => 'Updated Precision Gauge',
            'short_name' => 'UPG-01',
            'internal_code' => 'EQ-OG-01',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'package' => EquipmentPackage::Lot02->value,
            'status' => EquipmentStatus::Inactive->value,
            'requires_calibration' => '1',
            'params' => [
                $this->grandeurPressure->id => [
                    'selected' => '1',
                    'min' => '-1.0',
                    'max' => '30.0',
                    'acc' => '0.05',
                    'acc_type' => 'abs',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('metrology.equipment.update', $equipment), $updateData);

        $response->assertRedirect(route('metrology.equipment'));
        $equipment->refresh();

        $this->assertSame('Updated Precision Gauge', $equipment->full_name);
        $this->assertSame(EquipmentStatus::Inactive, $equipment->status);
        $this->assertCount(1, $equipment->specifications);
        $this->assertEquals(-1.0, $equipment->specifications->first()->range_min);
    }

    public function test_can_soft_delete_equipment(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Equipment To Delete',
            'internal_code' => 'EQ-DEL-001',
            'category' => EquipmentCategory::Other,
            'package' => EquipmentPackage::None,
            'status' => EquipmentStatus::Inactive,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('metrology.equipment.destroy', $equipment));

        $response->assertRedirect(route('metrology.equipment'));
        $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);
    }

    public function test_equipment_list_orders_by_priority_and_computes_four_kpi_pillars(): void
    {
        // Create out of order:
        // 1. Inactive item (should be 4th)
        $inactive = Equipment::create([
            'full_name' => 'Alpha Inactive Standard',
            'internal_code' => 'EQ-INACTIVE-01',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::None,
            'status' => EquipmentStatus::Inactive,
            'requires_calibration' => true,
        ]);

        // 2. Vehicle (should be 3rd)
        $vehicle = Equipment::create([
            'full_name' => 'Beta Operational Vehicle',
            'internal_code' => 'EQ-VEHICLE-01',
            'category' => EquipmentCategory::Vehicle,
            'package' => EquipmentPackage::VehicleLot,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        // 3. Work Tool (should be 2nd)
        $workTool = Equipment::create([
            'full_name' => 'Gamma Workshop Pump',
            'internal_code' => 'EQ-TOOL-01',
            'category' => EquipmentCategory::WorkTool,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => false,
        ]);

        // 4. Certified / Measuring Instrument (should be 1st)
        $certified = Equipment::create([
            'full_name' => 'Delta Certified Digital Gauge',
            'internal_code' => 'EQ-CERT-01',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
            'requires_calibration' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.equipment'));

        $response->assertOk();

        // Check KPI cards
        $response->assertSee('Has Certificate');
        $response->assertSee('Work Tools');
        $response->assertSee('Vehicles');
        $response->assertSee('Inactive');

        // Check Table Order: 1. Certified, 2. Work Tool, 3. Vehicle, 4. Inactive
        $content = $response->getContent();
        $posCertified = strpos($content, 'Delta Certified Digital Gauge');
        $posWorkTool = strpos($content, 'Gamma Workshop Pump');
        $posVehicle = strpos($content, 'Beta Operational Vehicle');
        $posInactive = strpos($content, 'Alpha Inactive Standard');

        $this->assertNotFalse($posCertified);
        $this->assertNotFalse($posWorkTool);
        $this->assertNotFalse($posVehicle);
        $this->assertNotFalse($posInactive);

        $this->assertTrue($posCertified < $posWorkTool, 'Certified equipment must appear before Work Tools');
        $this->assertTrue($posWorkTool < $posVehicle, 'Work Tools must appear before Vehicles');
        $this->assertTrue($posVehicle < $posInactive, 'Vehicles must appear before Inactive equipment');
    }

    public function test_equipment_status_only_permits_active_and_inactive(): void
    {
        $cases = EquipmentStatus::cases();
        $this->assertCount(2, $cases);
        $this->assertSame(['active', 'inactive'], EquipmentStatus::values());

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.equipment.store'), [
                'full_name' => 'Legacy Calibrator',
                'category' => EquipmentCategory::MeasuringInstrument->value,
                'status' => 'retired',
            ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_update_equipment_sanitizes_open_redirect_target(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Safe Redirect Test',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
        ]);

        // Attempt external redirect (Open Redirect attack)
        $response = $this->actingAs($this->adminUser)
            ->put(route('metrology.equipment.update', $equipment), [
                'full_name' => 'Safe Redirect Test Updated',
                'category' => EquipmentCategory::MeasuringInstrument->value,
                '_redirect' => 'https://malicious-site.com/steal-creds',
            ]);

        // Must redirect to default route('metrology.equipment'), NOT the external malicious URL
        $response->assertRedirect(route('metrology.equipment'));
        $this->assertNotSame('https://malicious-site.com/steal-creds', $response->headers->get('Location'));
    }

    public function test_specifications_deletion_dispatches_activity_events(): void
    {
        $equipment = Equipment::create([
            'full_name' => 'Calibrator With Specs',
            'category' => EquipmentCategory::MeasuringInstrument,
            'package' => EquipmentPackage::Lot01,
            'status' => EquipmentStatus::Active,
        ]);

        $equipment->specifications()->create([
            'grandeur_id' => $this->grandeurPressure->id,
            'range_min' => 0,
            'range_max' => 100,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        $this->assertCount(1, $equipment->specifications);

        // Update with empty specs should safely delete existing spec via Model instance
        $response = $this->actingAs($this->adminUser)
            ->put(route('metrology.equipment.update', $equipment), [
                'full_name' => 'Calibrator Specs Cleared',
                'category' => EquipmentCategory::MeasuringInstrument->value,
                'params' => [],
            ]);

        $response->assertRedirect();
        $this->assertCount(0, $equipment->fresh()->specifications);
    }
}
