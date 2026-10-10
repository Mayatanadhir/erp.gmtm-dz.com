<?php

declare(strict_types=1);

namespace Tests\Feature\Metrology;

use App\Enums\GrandeurDiscipline;
use App\Enums\GrandeurType;
use App\Models\Equipment;
use App\Models\EquipmentSpecification;
use App\Models\Grandeur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GrandeurFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $standardUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($superRole);

        $this->standardUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->standardUser->assignRole($userRole);
    }

    public function test_can_view_quantities_and_units_index_with_kpis(): void
    {
        Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'Bar',
            'type' => GrandeurType::Measurement,
        ]);

        Grandeur::create([
            'name' => 'Tension',
            'symbol' => 'V',
            'type' => GrandeurType::Source,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.units'));

        $response->assertOk();
        $response->assertDontSee('@js(');
        $response->assertSee('openEdit(JSON.parse(atob($el.dataset.item)))');
        $response->assertSee('openDelete(JSON.parse(atob($el.dataset.item)))');
        $response->assertSee('Pression');
        $response->assertSee('Bar');
        $response->assertSee('Tension');
        $response->assertSee('V');
    }

    public function test_can_create_new_grandeur_unit(): void
    {
        $payload = [
            'name' => 'Débit Massique',
            'symbol' => 'kg/h',
            'type' => GrandeurType::Measurement->value,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('metrology.units.store'), $payload);

        $response->assertRedirect(route('metrology.units'));
        $this->assertDatabaseHas('grandeurs', [
            'name' => 'Débit Massique',
            'symbol' => 'kg/h',
            'type' => 'measurement',
        ]);
    }

    public function test_can_update_existing_grandeur_unit(): void
    {
        $grandeur = Grandeur::create([
            'name' => 'Température',
            'symbol' => 'C',
            'type' => GrandeurType::Measurement,
        ]);

        $updatePayload = [
            'name' => 'Température Ambiante',
            'symbol' => '°C',
            'type' => GrandeurType::Source->value,
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('metrology.units.update', $grandeur), $updatePayload);

        $response->assertRedirect(route('metrology.units'));
        $grandeur->refresh();

        $this->assertSame('Température Ambiante', $grandeur->name);
        $this->assertSame('°C', $grandeur->symbol);
        $this->assertSame(GrandeurType::Source, $grandeur->type);
    }

    public function test_cannot_delete_grandeur_linked_to_equipment_specifications(): void
    {
        $grandeur = Grandeur::create([
            'name' => 'Pression',
            'symbol' => 'bar',
            'type' => GrandeurType::Measurement,
        ]);

        $equipment = Equipment::create([
            'full_name' => 'Test Calibrator',
            'category' => 'measuring_instrument',
            'package' => 'lot_01',
            'status' => 'active',
            'requires_calibration' => true,
        ]);

        EquipmentSpecification::create([
            'equipment_id' => $equipment->id,
            'grandeur_id' => $grandeur->id,
            'range_min' => 0.0,
            'range_max' => 10.0,
            'accuracy_value' => 0.05,
            'accuracy_type' => '%',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('metrology.units.destroy', $grandeur));

        $response->assertRedirect(route('metrology.units'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('grandeurs', ['id' => $grandeur->id]);
    }

    public function test_can_delete_unlinked_grandeur(): void
    {
        $grandeur = Grandeur::create([
            'name' => 'Unused Dimension',
            'symbol' => 'ud',
            'type' => GrandeurType::Source,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('metrology.units.destroy', $grandeur));

        $response->assertRedirect(route('metrology.units'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('grandeurs', ['id' => $grandeur->id]);
    }

    public function test_standard_user_cannot_access_units_management_without_permission(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('metrology.units'));
        $response->assertForbidden();
    }

    public function test_grandeur_discipline_detection_and_visual_mapping(): void
    {
        $temp = Grandeur::create(['name' => 'Température', 'symbol' => '°C', 'type' => GrandeurType::Measurement]);
        $press = Grandeur::create(['name' => 'Pression', 'symbol' => 'Bar', 'type' => GrandeurType::Measurement]);
        $curr = Grandeur::create(['name' => 'Courant', 'symbol' => 'mA', 'type' => GrandeurType::Source]);
        $volt = Grandeur::create(['name' => 'Tension', 'symbol' => 'V', 'type' => GrandeurType::Source]);
        $res = Grandeur::create(['name' => 'Résistance', 'symbol' => 'Ω', 'type' => GrandeurType::Measurement]);
        $freq = Grandeur::create(['name' => 'Frequence', 'symbol' => 'Hz', 'type' => GrandeurType::Source]);
        $pulse = Grandeur::create(['name' => 'Impultion', 'symbol' => 'imp', 'type' => GrandeurType::Source]);

        $this->assertSame(GrandeurDiscipline::Temperature, $temp->discipline());
        $this->assertSame('fa-temperature-high', $temp->discipline()->icon());
        $this->assertSame('rose', $temp->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Pressure, $press->discipline());
        $this->assertSame('fa-tachometer-alt', $press->discipline()->icon());
        $this->assertSame('sky', $press->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Current, $curr->discipline());
        $this->assertSame('amber', $curr->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Voltage, $volt->discipline());
        $this->assertSame('indigo', $volt->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Resistance, $res->discipline());
        $this->assertSame('emerald', $res->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Frequency, $freq->discipline());
        $this->assertSame('fuchsia', $freq->discipline()->colorKey());

        $this->assertSame(GrandeurDiscipline::Pulse, $pulse->discipline());
        $this->assertSame('purple', $pulse->discipline()->colorKey());
    }

    public function test_units_index_renders_grandeur_icons_and_discipline_badges(): void
    {
        Grandeur::create(['name' => 'Température', 'symbol' => '°C', 'type' => GrandeurType::Measurement]);
        Grandeur::create(['name' => 'Pression', 'symbol' => 'Bar', 'type' => GrandeurType::Measurement]);

        $response = $this->actingAs($this->adminUser)->get(route('metrology.units'));

        $response->assertOk();
        $response->assertSee('Température');
        $response->assertSee('Pression');
        $response->assertSee('Bar');
        $response->assertSee('°C');
    }
}
