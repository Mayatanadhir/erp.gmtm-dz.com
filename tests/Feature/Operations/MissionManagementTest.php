<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Enums\EmployeePosition;
use App\Enums\EquipmentCategory;
use App\Enums\EquipmentStatus;
use App\Enums\MissionDeploymentStatus;
use App\Enums\MissionOrderStatus;
use App\Enums\MissionStatus;
use App\Interfaces\MissionOrderRepositoryInterface;
use App\Interfaces\MissionRepositoryInterface;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\Mission;
use App\Models\MissionOrder;
use App\Models\Site;
use App\Models\User;
use App\Services\MissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $standardUser;

    protected Site $site;

    protected Employee $employee1;

    protected Employee $employee2;

    protected Equipment $calibrator;

    protected Equipment $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->standardUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->standardUser->assignRole($userRole);

        $this->site = Site::create([
            'site_code' => 'TEST-001',
            'full_name' => 'Test Industrial Facility',
            'short_name' => 'TIF',
            'location' => 'Hassi Messaoud',
        ]);

        $this->employee1 = Employee::create([
            'full_name' => 'Ahmed Brahimi',
            'registration_number' => 'EMP-001',
            'position' => EmployeePosition::SeniorMeteringEngineer->value,
            'status' => 'active',
            'join_date' => '2023-01-01',
            'salary' => 80000,
            'daily_rate' => 10000,
            'address' => 'Alger',
        ]);

        $this->employee2 = Employee::create([
            'full_name' => 'Karim Meziane',
            'registration_number' => 'EMP-002',
            'position' => EmployeePosition::MeteringTechnician->value,
            'status' => 'active',
            'join_date' => '2023-05-01',
            'salary' => 60000,
            'daily_rate' => 7000,
            'address' => 'Oran',
        ]);

        $this->calibrator = Equipment::create([
            'internal_code' => 'CAL-001',
            'full_name' => 'Digital Pressure Calibrator',
            'category' => EquipmentCategory::MeasuringInstrument->value,
            'status' => EquipmentStatus::Active->value,
        ]);

        $this->vehicle = Equipment::create([
            'internal_code' => 'VEH-001',
            'full_name' => 'Toyota Hilux 4x4',
            'serial_number' => '00123-122-16',
            'category' => EquipmentCategory::Vehicle->value,
            'status' => EquipmentStatus::Active->value,
        ]);
    }

    /**
     * 1. Guests are redirected to login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('operations.missions'));
        $response->assertRedirect(route('login'));
    }

    /**
     * 2. Super admin can view missions index.
     */
    public function test_super_admin_can_view_missions_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions'));
        $response->assertOk();
        $response->assertViewIs('operations.missions.index');
    }

    /**
     * 3. Creating a mission assigns team, vehicle, calibrators, and generates reference.
     */
    public function test_can_create_mission_with_team_and_equipment(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'mob_dmob_days' => 2,
            'description' => 'Annual loop inspection and calibration',
            'chief_id' => $this->employee1->id,
            'employees' => [
                ['id' => $this->employee1->id, 'is_leader' => 1],
                ['id' => $this->employee2->id, 'is_leader' => 0],
            ],
            'vehicle_id' => $this->vehicle->id,
            'equipments' => [$this->calibrator->id],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.store'), $payload);

        $this->assertDatabaseHas('missions', [
            'site_id' => $this->site->id,
            'status' => MissionStatus::Planned->value,
            'mob_dmob_days' => 2,
        ]);

        /** @var Mission $mission */
        $mission = Mission::first();
        $this->assertNotNull($mission);
        $this->assertStringStartsWith('M-2026-', $mission->reference);
        $this->assertCount(2, $mission->missionOrders);
        $this->assertCount(2, $mission->deployments); // 1 vehicle + 1 calibrator

        $response->assertRedirect(route('operations.missions.show', $mission->id));
    }

    /**
     * 4. Single team leader validation blocks multiple leaders.
     */
    public function test_cannot_assign_multiple_team_leaders(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'chief_id' => $this->employee1->id,
            'employees' => [
                ['id' => $this->employee1->id, 'is_leader' => 1],
                ['id' => $this->employee2->id, 'is_leader' => 1],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.store'), $payload);
        $response->assertSessionHasErrors(['employees']);
    }

    /**
     * 4b. Missing chief_id validation error.
     */
    public function test_cannot_create_mission_without_leader(): void
    {
        $payload = [
            'site_id' => $this->site->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'employees' => [
                ['id' => $this->employee1->id, 'is_leader' => 0],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.store'), $payload);
        $response->assertSessionHasErrors(['chief_id']);
    }

    /**
     * 5. Activating a mission transitions state to Active.
     */
    public function test_can_activate_planned_mission(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-999',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $mission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.activate', $mission->id));

        $response->assertRedirect(route('operations.missions.show', $mission->id));
        $this->assertEquals(MissionStatus::Active, $mission->refresh()->status);
    }

    /**
     * 6. Activating mission detects temporal conflict if employee is in another active mission.
     */
    public function test_activation_blocked_when_employee_has_temporal_conflict(): void
    {
        // 1st active mission
        /** @var Mission $activeMission */
        $activeMission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-001',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Active->value,
        ]);
        $activeMission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
        ]);

        // 2nd planned mission overlapping in dates with the same employee
        /** @var Mission $plannedMission */
        $plannedMission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-002',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-15',
            'status' => MissionStatus::Planned->value,
        ]);
        $plannedMission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.activate', $plannedMission->id));

        $response->assertSessionHas('error');
        $this->assertEquals(MissionStatus::Planned, $plannedMission->refresh()->status);
    }

    /**
     * 7. Completing a mission sets status to Completed and marks deployments returned.
     */
    public function test_can_complete_active_mission(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-001',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Active->value,
        ]);

        $deployment = $mission->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.complete', $mission->id));

        $response->assertRedirect(route('operations.missions.show', $mission->id));
        $this->assertEquals(MissionStatus::Completed, $mission->refresh()->status);
        $this->assertEquals('returned', $deployment->refresh()->status->value);
    }

    /**
     * 8. Can view mission statistics page.
     */
    public function test_can_view_mission_statistics(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-001',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'mob_dmob_days' => 2,
            'status' => MissionStatus::Completed->value,
        ]);

        $mission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'daily_rate' => 10000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.statistics', $mission->id));
        $response->assertOk();
        $response->assertViewIs('operations.missions.statistics');
        $response->assertSee('100,000.00'); // 10 days * 10,000 DZD
    }

    /**
     * 9. Can print official travel order and auto-generate reference if missing.
     */
    public function test_can_print_travel_order_and_auto_generate_reference(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-001',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $order = $mission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'destination' => 'Alger - Hassi Messaoud - Alger',
            'order_reference' => null,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.orders.print', [$mission->id, $order->id]));

        $response->assertOk();
        $this->assertNotNull($order->refresh()->order_reference);
        $this->assertStringContainsString('/ALG/', $order->order_reference);
    }

    /**
     * 10. Vehicles are excluded from the Mobilized Equipment table on show page.
     */
    public function test_show_page_excludes_vehicles_from_deployed_equipment_table(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-002',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        // Deploy calibrator
        $mission->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'active',
            'deployed_at' => '2026-10-01',
        ]);

        // Deploy vehicle
        $mission->deployments()->create([
            'equipment_id' => $this->vehicle->id,
            'status' => 'active',
            'deployed_at' => '2026-10-01',
        ]);

        $this->assertCount(2, $mission->deployments);
        $this->assertCount(1, $mission->technical_deployments);
        $this->assertEquals($this->calibrator->id, $mission->technical_deployments->first()->equipment_id);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.show', $mission->id));
        $response->assertOk();
        $response->assertSee($this->calibrator->full_name);
        $response->assertDontSee($this->vehicle->full_name);
    }

    /**
     * 12. Resources from a COMPLETED mission must NOT block activation of a new overlapping mission.
     *
     * Regression test for: conflict check falsely blocking missions against completed ones.
     */
    public function test_completed_mission_does_not_block_new_mission_activation(): void
    {
        // Mission A: completed, occupies employee1 + calibrator for the same period
        /** @var Mission $missionA */
        $missionA = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-A-DONE',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-20',
            'status' => MissionStatus::Completed->value,
        ]);

        $missionA->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'completed',
            'started_at' => '2026-11-01',
            'ended_at' => '2026-11-20',
            'daily_rate' => $this->employee1->daily_rate,
            'destination' => 'Alger - Hassi Messaoud - Alger',
        ]);

        $missionA->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'returned',
            'deployed_at' => '2026-11-01',
            'returned_at' => '2026-11-20',
        ]);

        // Mission B: planned, same period, same resources → should activate without conflict
        /** @var Mission $missionB */
        $missionB = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-B-NEW',
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-15',
            'status' => MissionStatus::Planned->value,
        ]);

        $missionB->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
            'started_at' => '2026-11-05',
            'ended_at' => '2026-11-15',
            'daily_rate' => $this->employee1->daily_rate,
            'destination' => 'Alger - Hassi Messaoud - Alger',
        ]);

        $missionB->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'active',
            'deployed_at' => '2026-11-05',
        ]);

        // Activating Mission B must succeed — completed mission A releases its resources
        $response = $this->actingAs($this->superAdmin)
            ->post(route('operations.missions.activate', $missionB->id));

        $response->assertRedirect(route('operations.missions.show', $missionB->id));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('missions', [
            'id' => $missionB->id,
            'status' => MissionStatus::Active->value,
        ]);
    }

    /**
     * 13. An ACTIVE (running) mission must block activation of another overlapping mission
     *     sharing the same employee or equipment.
     *
     * Planned missions intentionally share resources freely — only Active missions create
     * a hard block to prevent deploying the same asset to two running missions simultaneously.
     */
    public function test_active_mission_blocks_overlapping_mission_activation(): void
    {
        // Mission A: already active, occupies employee1 for the same period
        /** @var Mission $missionA */
        $missionA = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-A-RUNNING',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => MissionStatus::Active->value,
        ]);

        $missionA->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
            'started_at' => '2026-12-01',
            'ended_at' => '2026-12-31',
            'daily_rate' => $this->employee1->daily_rate,
            'destination' => 'Alger - Hassi Messaoud - Alger',
        ]);

        // Mission B: planned, overlapping period, same employee → must be blocked at activation
        /** @var Mission $missionB */
        $missionB = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-B-BLOCKED',
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-20',
            'status' => MissionStatus::Planned->value,
        ]);

        $missionB->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
            'started_at' => '2026-12-10',
            'ended_at' => '2026-12-20',
            'daily_rate' => $this->employee1->daily_rate,
            'destination' => 'Alger - Hassi Messaoud - Alger',
        ]);

        // Activating Mission B must fail — Mission A is ACTIVE and occupies the same employee
        $response = $this->actingAs($this->superAdmin)
            ->post(route('operations.missions.activate', $missionB->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('missions', [
            'id' => $missionB->id,
            'status' => MissionStatus::Planned->value,
        ]);
    }

    /**
     * 11. SuperAdmin can view and print the mission equipment manifest.
     */
    public function test_can_view_mission_equipment_manifest(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-003',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $mission->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'active',
            'deployed_at' => '2026-10-01',
        ]);

        $mission->deployments()->create([
            'equipment_id' => $this->vehicle->id,
            'status' => 'active',
            'deployed_at' => '2026-10-01',
        ]);

        // Guest is redirected
        $this->get(route('operations.missions.equipments', $mission->id))
            ->assertRedirect(route('login'));

        // SuperAdmin can view the manifest
        $response = $this->actingAs($this->superAdmin)
            ->get(route('operations.missions.equipments', $mission->id));

        $response->assertOk();
        $response->assertViewIs('operations.missions.equipments-list');
        $response->assertSee($mission->reference);
        $response->assertSee($this->calibrator->full_name);
    }

    /**
     * 14. Can update a planned mission including team members and designated leader.
     */
    public function test_can_update_planned_mission_and_change_leader(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-EDIT',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $mission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'destination' => 'Site',
        ]);

        // 1. Can view edit page
        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('operations.missions.edit', $mission->id));
        $editResponse->assertOk();
        $editResponse->assertViewIs('operations.missions.edit');

        // 2. Can submit update with employee2 as the new leader
        $updatePayload = [
            'site_id' => $this->site->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-15',
            'mob_dmob_days' => 1,
            'description' => 'Updated mission objectives',
            'employees' => [$this->employee1->id, $this->employee2->id],
            'chief_id' => $this->employee2->id,
            'vehicle_id' => $this->vehicle->id,
            'equipments' => [$this->calibrator->id],
        ];

        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('operations.missions.update', $mission->id), $updatePayload);

        $updateResponse->assertRedirect(route('operations.missions.show', $mission->id));

        $mission->refresh();
        $this->assertEquals('2026-10-05', $mission->start_date->format('Y-m-d'));
        $this->assertEquals('2026-10-15', $mission->end_date->format('Y-m-d'));
        $this->assertCount(2, $mission->missionOrders);

        // Employee2 is now the leader
        $order1 = $mission->missionOrders()->where('employee_id', $this->employee1->id)->first();
        $order2 = $mission->missionOrders()->where('employee_id', $this->employee2->id)->first();

        $this->assertFalse((bool) $order1->is_leader);
        $this->assertTrue((bool) $order2->is_leader);

        // 3. Unassign employee1, leaving only employee2
        $unassignPayload = [
            'site_id' => $this->site->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-15',
            'employees' => [$this->employee2->id],
            'chief_id' => $this->employee2->id,
        ];

        $this->actingAs($this->superAdmin)
            ->put(route('operations.missions.update', $mission->id), $unassignPayload)
            ->assertRedirect(route('operations.missions.show', $mission->id));

        $mission->refresh();
        $this->assertCount(1, $mission->missionOrders);
        $this->assertCount(1, $mission->employees);
        $this->assertFalse($mission->employees->contains('id', $this->employee1->id));
        $this->assertTrue($mission->employees->contains('id', $this->employee2->id));

        // In edit view, employee1 is no longer in assigned IDs
        $editAgain = $this->actingAs($this->superAdmin)->get(route('operations.missions.edit', $mission->id));
        $editAgain->assertViewHas('assignedEmployeeIds', [$this->employee2->id]);
    }

    /**
     * 15. Can delete a planned mission and redirect to operations.missions.
     */
    public function test_can_delete_planned_mission(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'reference' => 'M-2026-DEL',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $mission->missionOrders()->create([
            'employee_id' => $this->employee1->id,
            'is_leader' => true,
            'status' => 'active',
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'destination' => 'Site',
        ]);

        $mission->deployments()->create([
            'equipment_id' => $this->calibrator->id,
            'status' => 'active',
            'deployed_at' => '2026-10-01',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('operations.missions.destroy', $mission->id));

        $response->assertRedirect(route('operations.missions'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('missions', ['id' => $mission->id]);
    }

    /**
     * 16. Create view receives contracts and sites with customer_id.
     */
    public function test_create_view_receives_contracts_and_sites_with_customer_id(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-TEST-001',
            'object' => 'Test calibration agreement',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 500000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.create'));

        $response->assertOk();
        $response->assertViewIs('operations.missions.create');
        $response->assertViewHas('contracts');
        $response->assertViewHas('sites');
        $response->assertSee('CTR-TEST-001');
    }

    /**
     * 17. Can create mission with contract reference and legacy equipment array-of-objects.
     */
    public function test_can_create_mission_with_contract_and_legacy_equipment_format(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-LEGACY-002',
            'object' => 'Field testing contract',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 800000,
        ]);

        $payload = [
            'site_id' => $this->site->id,
            'contract_id' => $contract->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'mob_dmob_days' => 1.5,
            'chief_id' => $this->employee1->id,
            'employees' => [
                ['id' => $this->employee1->id, 'is_leader' => 1],
                ['id' => $this->employee2->id, 'is_leader' => 0],
            ],
            'vehicle_id' => $this->vehicle->id,
            'equipments' => [
                ['id' => $this->calibrator->id],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.store'), $payload);

        $this->assertDatabaseHas('missions', [
            'site_id' => $this->site->id,
            'contract_id' => $contract->id,
            'mob_dmob_days' => 1.5,
        ]);

        /** @var Mission $mission */
        $mission = Mission::where('contract_id', $contract->id)->first();
        $this->assertNotNull($mission);
        $this->assertCount(2, $mission->missionOrders);
        $this->assertCount(2, $mission->deployments); // 1 vehicle + 1 calibrator

        $response->assertRedirect(route('operations.missions.show', $mission->id));
    }

    /**
     * 18. Create view only shows active contracts and excludes expired contracts.
     */
    public function test_create_view_only_shows_active_contracts_and_excludes_expired_contracts(): void
    {
        $activeContract = Contract::create([
            'reference' => 'CTR-ACTIVE-001',
            'object' => 'Active Contract',
            'date_signature' => now()->subMonth()->format('Y-m-d'),
            'duree' => 12,
            'montant_global_prevu' => 500000,
        ]);

        $expiredContract = Contract::create([
            'reference' => 'CTR-EXPIRED-001',
            'object' => 'Expired Contract',
            'date_signature' => now()->subMonths(12)->format('Y-m-d'),
            'duree' => 3,
            'montant_global_prevu' => 300000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.create'));

        $response->assertOk();
        $response->assertSee('CTR-ACTIVE-001');
        $response->assertDontSee('CTR-EXPIRED-001');
    }

    /**
     * 19. Cannot create mission with an expired contract.
     */
    public function test_cannot_create_mission_with_expired_contract(): void
    {
        $expiredContract = Contract::create([
            'reference' => 'CTR-EXPIRED-002',
            'object' => 'Expired Contract 2',
            'date_signature' => now()->subMonths(12)->format('Y-m-d'),
            'duree' => 3,
            'montant_global_prevu' => 300000,
        ]);

        $payload = [
            'site_id' => $this->site->id,
            'contract_id' => $expiredContract->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'chief_id' => $this->employee1->id,
            'employees' => [
                ['id' => $this->employee1->id, 'is_leader' => 1],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.missions.store'), $payload);

        $response->assertSessionHasErrors('contract_id');
        $this->assertDatabaseMissing('missions', [
            'contract_id' => $expiredContract->id,
        ]);
    }

    /**
     * 20. Edit view shows active contracts and preserves mission existing expired contract.
     */
    public function test_edit_view_shows_active_contracts_and_preserves_mission_expired_contract(): void
    {
        $missionContract = Contract::create([
            'reference' => 'CTR-MISSION-EXP',
            'object' => 'Attached Expired Contract',
            'date_signature' => now()->subMonths(10)->format('Y-m-d'),
            'duree' => 2,
            'montant_global_prevu' => 400000,
        ]);

        $otherExpiredContract = Contract::create([
            'reference' => 'CTR-OTHER-EXP',
            'object' => 'Other Expired Contract',
            'date_signature' => now()->subMonths(10)->format('Y-m-d'),
            'duree' => 2,
            'montant_global_prevu' => 400000,
        ]);

        $mission = Mission::create([
            'reference' => 'M-2026-EXP',
            'site_id' => $this->site->id,
            'contract_id' => $missionContract->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.edit', $mission->id));

        $response->assertOk();
        $response->assertSee('CTR-MISSION-EXP');
        $response->assertDontSee('CTR-OTHER-EXP');
    }

    /**
     * 21. Attempting to edit non-planned mission redirects to show page with warning.
     */
    public function test_cannot_edit_non_planned_mission_and_redirects_with_warning(): void
    {
        $activeMission = Mission::create([
            'reference' => 'M-2026-ACT-EDIT',
            'site_id' => $this->site->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => MissionStatus::Active->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.edit', $activeMission->id));

        $response->assertRedirect(route('operations.missions.show', $activeMission->id));
        $response->assertSessionHas('warning', __('Only planned missions can be modified.'));

        $completedMission = Mission::create([
            'reference' => 'M-2026-COMP-EDIT',
            'site_id' => $this->site->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => MissionStatus::Completed->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.edit', $completedMission->id));

        $response->assertRedirect(route('operations.missions.show', $completedMission->id));
        $response->assertSessionHas('warning', __('Only planned missions can be modified.'));
    }

    /**
     * 22. Updating a mission preserves customized employee order dates and updates destination when site changes.
     */
    public function test_update_mission_preserves_customized_employee_dates_and_updates_destination(): void
    {
        $site2 = Site::create([
            'site_code' => 'SIT-002',
            'full_name' => 'Secondary Operating Field',
            'short_name' => 'Site Two',
            'location' => 'In Amenas Gas Field',
        ]);

        $mission = app(MissionService::class)->createMission([
            'site_id' => $this->site->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'mob_dmob_days' => 1.5,
            'description' => 'Initial Mission',
            'employees' => [$this->employee1->id, $this->employee2->id],
            'chief_id' => $this->employee1->id,
        ]);

        // Customize employee2's dates (partial assignment within mission)
        $order2 = $mission->missionOrders()->where('employee_id', $this->employee2->id)->first();
        $order2->update([
            'started_at' => '2026-10-03',
            'ended_at' => '2026-10-07',
        ]);

        // Update the mission to new dates and new site
        $payload = [
            'site_id' => $site2->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-20',
            'mob_dmob_days' => 2.0,
            'description' => 'Updated Mission Site',
            'employees' => [$this->employee1->id, $this->employee2->id],
            'chief_id' => $this->employee1->id,
        ];

        $this->actingAs($this->superAdmin)
            ->put(route('operations.missions.update', $mission->id), $payload)
            ->assertRedirect(route('operations.missions.show', $mission->id));

        $mission->refresh();
        $this->assertEquals($site2->id, $mission->site_id);
        $this->assertEquals(2.0, (float) $mission->mob_dmob_days);

        $refreshedOrder1 = $mission->missionOrders()->where('employee_id', $this->employee1->id)->first();
        $refreshedOrder2 = $mission->missionOrders()->where('employee_id', $this->employee2->id)->first();

        // Order 1 was full-span, so it updated to new mission span
        $this->assertEquals('2026-10-01', $refreshedOrder1->started_at->format('Y-m-d'));
        $this->assertEquals('2026-10-20', $refreshedOrder1->ended_at->format('Y-m-d'));
        $this->assertEquals('Alger - In Amenas Gas Field - Alger', $refreshedOrder1->destination);

        // Order 2 was customized, so its unique dates were strictly preserved
        $this->assertEquals('2026-10-03', $refreshedOrder2->started_at->format('Y-m-d'));
        $this->assertEquals('2026-10-07', $refreshedOrder2->ended_at->format('Y-m-d'));
        $this->assertEquals('Oran - In Amenas Gas Field - Oran', $refreshedOrder2->destination);
    }

    /**
     * 23. Reference generation parses integer sequences and correctly exceeds 999.
     */
    public function test_reference_generation_handles_sequences_past_999(): void
    {
        $year = (int) date('Y');
        Mission::create([
            'reference' => "M-{$year}-999",
            'site_id' => $this->site->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => MissionStatus::Planned->value,
        ]);

        $nextRef = app(MissionRepositoryInterface::class)->getNextReference();
        $this->assertEquals("M-{$year}-1000", $nextRef);
    }

    /**
     * 24. Travel order reference generation parses integer sequences and correctly exceeds 999.
     */
    public function test_travel_order_reference_generation_handles_sequences_past_999(): void
    {
        $mission = Mission::create([
            'reference' => 'M-2026-ORD-SEQ',
            'site_id' => $this->site->id,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-10',
            'status' => MissionStatus::Planned->value,
        ]);

        MissionOrder::create([
            'mission_id' => $mission->id,
            'employee_id' => $this->employee1->id,
            'order_reference' => '999/ALG/26',
            'status' => MissionOrderStatus::Active->value,
            'destination' => 'Site A',
        ]);

        $nextOrderRef = app(MissionOrderRepositoryInterface::class)->getNextOrderReference(2026);
        $this->assertEquals('1000/ALG/26', $nextOrderRef);
    }

    /**
     * 25. Complete and revert mission operations preserve partial cancelled and damaged statuses.
     */
    public function test_complete_and_revert_mission_preserves_partial_cancelled_and_damaged_statuses(): void
    {
        $mission = app(MissionService::class)->createMission([
            'site_id' => $this->site->id,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10',
            'employees' => [$this->employee1->id, $this->employee2->id],
            'chief_id' => $this->employee1->id,
            'equipments' => [$this->calibrator->id],
        ]);

        $missionService = app(MissionService::class);
        $missionService->activateMission($mission);

        // Manually mark employee2's order as Cancelled
        $order2 = $mission->missionOrders()->where('employee_id', $this->employee2->id)->first();
        $order2->update(['status' => MissionOrderStatus::Cancelled->value]);

        // Manually mark deployment as Damaged
        $deployment = $mission->deployments()->first();
        $deployment->update(['status' => MissionDeploymentStatus::Damaged->value]);

        // Complete the mission
        $missionService->completeMission($mission);
        $mission->refresh();

        $this->assertEquals(MissionStatus::Completed, $mission->status);
        $this->assertEquals(MissionOrderStatus::Completed, $mission->missionOrders()->where('employee_id', $this->employee1->id)->first()->status);
        // Order 2 remained Cancelled
        $this->assertEquals(MissionOrderStatus::Cancelled, $mission->missionOrders()->where('employee_id', $this->employee2->id)->first()->status);
        // Deployment remained Damaged
        $this->assertEquals(MissionDeploymentStatus::Damaged, $mission->deployments()->first()->status);

        // Revert the mission
        $missionService->revertMission($mission);
        $mission->refresh();

        $this->assertEquals(MissionStatus::Planned, $mission->status);
        $this->assertEquals(MissionOrderStatus::Active, $mission->missionOrders()->where('employee_id', $this->employee1->id)->first()->status);
        // Order 2 still Cancelled
        $this->assertEquals(MissionOrderStatus::Cancelled, $mission->missionOrders()->where('employee_id', $this->employee2->id)->first()->status);
        // Deployment still Damaged
        $this->assertEquals(MissionDeploymentStatus::Damaged, $mission->deployments()->first()->status);
    }
}
