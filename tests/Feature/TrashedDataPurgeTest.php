<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\DataPruningService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashedDataPurgeTest extends TestCase
{
    use RefreshDatabase;

    private DataPruningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->service = app(DataPruningService::class);
    }

    public function test_unauthenticated_and_unauthorized_users_are_restricted(): void
    {
        $this->get(route('system-tables.trashed'))
            ->assertRedirect(route('login'));

        $this->get(route('system-tables.pruning.trashed.records', 'employees'))
            ->assertRedirect(route('login'));

        $this->post(route('system-tables.pruning.trashed.purge-single', ['table' => 'employees', 'id' => 1]))
            ->assertRedirect(route('login'));

        $this->post(route('system-tables.pruning.trashed.purge-table', 'employees'))
            ->assertRedirect(route('login'));

        $this->post(route('system-tables.pruning.trashed.purge-all'))
            ->assertRedirect(route('login'));

        /** @var User $regularUser */
        $regularUser = User::factory()->create();

        $this->actingAs($regularUser)
            ->get(route('system-tables.trashed'))
            ->assertForbidden();

        $this->actingAs($regularUser)
            ->get(route('system-tables.pruning.trashed.records', 'employees'))
            ->assertForbidden();

        $this->actingAs($regularUser)
            ->post(route('system-tables.pruning.trashed.purge-all'))
            ->assertForbidden();
    }

    public function test_service_detects_soft_deleted_records_correctly(): void
    {
        // Create an active employee and a soft-deleted employee
        $active = Employee::factory()->create(['full_name' => 'Active User']);
        $trashed = Employee::factory()->create(['full_name' => 'Trashed User']);
        $trashed->delete(); // Soft delete

        $tables = $this->service->getTablesWithTrashedData();
        $employeeTable = collect($tables)->firstWhere('table', 'employees');

        $this->assertNotNull($employeeTable);
        $this->assertSame('Employee', $employeeTable['model']);
        $this->assertGreaterThanOrEqual(1, $employeeTable['trashed_count']);
        $this->assertNotNull($employeeTable['newest_deleted_at']);

        // Clean tables (0 trashed) must be omitted by default
        $cleanTables = collect($tables)->filter(fn (array $t): bool => $t['trashed_count'] === 0);
        $this->assertCount(0, $cleanTables);

        // When onlyWithTrashed is explicitly false, clean tables are included
        $allTables = $this->service->getTablesWithTrashedData(onlyWithTrashed: false);
        $this->assertGreaterThanOrEqual(count($tables), count($allTables));
    }

    public function test_admin_can_inspect_trashed_records_endpoint(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        $emp = Employee::factory()->create(['full_name' => 'InspectMe Smith']);
        $emp->delete();

        $response = $this->actingAs($user)->getJson(route('system-tables.pruning.trashed.records', 'employees'));

        $response->assertOk();
        $response->assertJsonStructure([
            'table',
            'model',
            'total_trashed',
            'columns',
            'records',
        ]);
        $this->assertSame('employees', $response->json('table'));
        $this->assertGreaterThanOrEqual(1, $response->json('total_trashed'));
    }

    public function test_admin_can_force_delete_single_trashed_record(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        $emp = Employee::factory()->create(['full_name' => 'SingleDelete Test']);
        $empId = $emp->id;
        $emp->delete(); // Soft deleted

        $this->assertSoftDeleted('employees', ['id' => $empId]);

        $response = $this->actingAs($user)->postJson(route('system-tables.pruning.trashed.purge-single', [
            'table' => 'employees',
            'id' => $empId,
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'id' => (string) $empId,
        ]);

        // Record must be completely erased from the database
        $this->assertDatabaseMissing('employees', ['id' => $empId]);
    }

    public function test_purge_table_requires_confirmation_token(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        $emp = Employee::factory()->create();
        $emp->delete();

        // 1. Invalid confirmation fails
        $badResponse = $this->actingAs($user)->post(route('system-tables.pruning.trashed.purge-table', 'employees'), [
            'confirmation' => 'WRONG_WORD',
        ]);

        $badResponse->assertRedirect(route('system-tables.trashed'));
        $badResponse->assertSessionHasErrors('trashed');
        $this->assertSoftDeleted('employees', ['id' => $emp->id]);

        // 2. Valid confirmation succeeds
        $goodResponse = $this->actingAs($user)->post(route('system-tables.pruning.trashed.purge-table', 'employees'), [
            'confirmation' => 'PURGE',
        ]);

        $goodResponse->assertRedirect(route('system-tables.trashed'));
        $goodResponse->assertSessionHas('status');
        $this->assertDatabaseMissing('employees', ['id' => $emp->id]);
    }

    public function test_system_wide_purge_permanently_wipes_all_trashed_across_tables(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        // Active records
        $activeEmp = Employee::factory()->create(['full_name' => 'KeepAlive']);

        // Soft-deleted records
        $trashedEmp = Employee::factory()->create(['full_name' => 'DropMe']);
        $trashedEmpId = $trashedEmp->id;
        $trashedEmp->delete();

        // 1. Invalid confirmation fails
        $badResponse = $this->actingAs($user)->post(route('system-tables.pruning.trashed.purge-all'), [
            'confirmation' => 'INVALID',
        ]);
        $badResponse->assertRedirect(route('system-tables.trashed'));
        $badResponse->assertSessionHasErrors('trashed');
        $this->assertSoftDeleted('employees', ['id' => $trashedEmpId]);

        // 2. Valid confirmation 'FORCE DELETE' succeeds
        $goodResponse = $this->actingAs($user)->post(route('system-tables.pruning.trashed.purge-all'), [
            'confirmation' => 'FORCE DELETE',
        ]);

        $goodResponse->assertRedirect(route('system-tables.trashed'));
        $goodResponse->assertSessionHas('status');

        // Trashed record is gone
        $this->assertDatabaseMissing('employees', ['id' => $trashedEmpId]);

        // Active record is still in database intact!
        $this->assertDatabaseHas('employees', ['id' => $activeEmp->id, 'deleted_at' => null]);
    }

    public function test_dedicated_trashed_dashboard_displays_soft_deleted_tables_and_actions(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        $emp = Employee::factory()->create();
        $emp->delete();

        // 1. Pruning dashboard links to trashed screen via the sidebar tabs component, but has no danger card
        $response = $this->actingAs($user)->get(route('system-tables.pruning'));
        $response->assertOk();
        $response->assertDontSee(__('Deep Trashed Inspector & Permanent Hard Purge'));
        $response->assertSee(route('system-tables.trashed'));

        // 2. Dedicated trashed screen contains the actual soft-deleted inspection table & purge buttons
        $trashedResponse = $this->actingAs($user)->get(route('system-tables.trashed'));
        $trashedResponse->assertOk();
        $trashedResponse->assertSee('employees');
        $trashedResponse->assertSee(__('Inspect'));
        $trashedResponse->assertSee(__('Hard Purge'));
        $trashedResponse->assertSee(route('system-tables.pruning'));
    }

    public function test_admin_can_access_dedicated_trashed_show_page(): void
    {
        /** @var User $user */
        $user = User::factory()->superAdmin()->create();

        $emp = Employee::factory()->create(['full_name' => 'ShowPage Employee']);
        $emp->delete();

        $response = $this->actingAs($user)->get(route('system-tables.trashed.show', ['table' => 'employees']));

        $response->assertOk();
        $response->assertSee('employees');
        $response->assertSee('ShowPage Employee');
        $response->assertSee(__('Preview Trashed Records'));
    }
}
