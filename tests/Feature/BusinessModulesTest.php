<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessModulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $standardUser;

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
    }

    public function test_super_admin_can_access_all_metrology_pages(): void
    {
        $routes = [
            'metrology.instruments',
            'metrology.equipment',
            'metrology.calibrator-movements',
            'metrology.calibration-certificates',
            'metrology.units',
            'metrology.reports',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
            $response->assertSee(route($route));
        }
    }

    public function test_super_admin_can_access_all_operations_pages(): void
    {
        $routes = [
            'operations.missions',
            'operations.contracts',
            'operations.attachments',
            'operations.article-types',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
            $response->assertSee(route($route));
        }
    }

    public function test_super_admin_can_access_all_financial_pages(): void
    {
        $routes = [
            'financial.warranties',
            'financial.expenses',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
            $response->assertSee(route($route));
        }
    }

    public function test_super_admin_can_access_all_analytics_pages(): void
    {
        $routes = [
            'analytics.forecasts',
            'analytics.statistics',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
            $response->assertSee(route($route));
        }
    }

    public function test_super_admin_can_access_all_master_data_pages(): void
    {
        $routes = [
            'master-data.clients',
            'master-data.employees',
            'master-data.sites',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
            $response->assertSee(route($route));
        }
    }

    public function test_super_admin_can_access_all_module_dashboards(): void
    {
        $dashboardRoutes = [
            'dashboard',
            'dashboard_metrology',
            'metrology.index',
            'dashboard_operations',
            'operations.index',
            'dashboard_financial',
            'financial.index',
            'dashboard_analytics',
            'analytics.index',
            'dashboard_master_data',
            'master-data.index',
        ];

        foreach ($dashboardRoutes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $response->assertOk();
        }
    }

    public function test_standard_user_without_permissions_is_forbidden(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('dashboard_metrology'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_operations'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_financial'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_analytics'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_master_data'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('metrology.instruments'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('operations.missions'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('financial.warranties'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('financial.expenses'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('master-data.clients'));
        $response->assertForbidden();
    }

    public function test_standard_user_with_specific_permission_can_access_only_granted_page(): void
    {
        $permission = Permission::findByName('view measuring instruments', 'web');
        $this->standardUser->givePermissionTo($permission);

        $response = $this->actingAs($this->standardUser)->get(route('metrology.instruments'));
        $response->assertOk();

        // Still forbidden on other pages
        $response = $this->actingAs($this->standardUser)->get(route('metrology.equipment'));
        $response->assertForbidden();
    }

    public function test_standard_user_with_module_view_permission_can_access_module_dashboard(): void
    {
        $permission = Permission::findByName('view metrology', 'web');
        $this->standardUser->givePermissionTo($permission);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_metrology'));
        $response->assertOk();

        // Still forbidden on other module dashboards
        $response = $this->actingAs($this->standardUser)->get(route('dashboard_operations'));
        $response->assertForbidden();
    }

    public function test_operations_dashboard_cards_adhere_to_permissions(): void
    {
        // 1. Super Admin sees all cards
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard_operations'));
        $response->assertOk();
        $response->assertSee(route('operations.missions'));
        $response->assertSee(route('operations.contracts'));
        $response->assertSee(route('operations.attachments'));
        $response->assertSee(route('operations.article-types'));
        $response->assertDontSee(__('No Accessible Explorers'));

        // 2. User with only 'view operations' sees fallback empty state
        $operationsPerm = Permission::findByName('view operations', 'web');
        $this->standardUser->givePermissionTo($operationsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_operations'));
        $response->assertOk();
        $response->assertSee(__('No Accessible Explorers'));
        $response->assertDontSee(route('operations.missions'));
        $response->assertDontSee(route('operations.contracts'));

        // 3. User with 'view missions' sees only missions card and quick access
        $missionsPerm = Permission::findByName('view missions', 'web');
        $this->standardUser->givePermissionTo($missionsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_operations'));
        $response->assertOk();
        $response->assertSee(route('operations.missions'));
        $response->assertDontSee(route('operations.contracts'));
        $response->assertDontSee(route('operations.article-types'));
        $response->assertDontSee(__('No Accessible Explorers'));
    }

    public function test_metrology_dashboard_cards_adhere_to_permissions(): void
    {
        // 1. Super Admin sees all cards
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard_metrology'));
        $response->assertOk();
        $response->assertSee(route('metrology.instruments'));
        $response->assertSee(route('metrology.equipment'));
        $response->assertSee(route('metrology.calibrator-movements'));
        $response->assertSee(route('metrology.calibration-certificates'));
        $response->assertSee(route('metrology.units'));
        $response->assertSee(route('metrology.reports'));
        $response->assertDontSee(__('No Accessible Explorers'));

        // 2. User with only 'view metrology' sees fallback empty state
        $metrologyPerm = Permission::findByName('view metrology', 'web');
        $this->standardUser->givePermissionTo($metrologyPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_metrology'));
        $response->assertOk();
        $response->assertSee(__('No Accessible Explorers'));
        $response->assertDontSee(route('metrology.instruments'));

        // 3. User with 'view measuring instruments' sees only that card
        $instrumentsPerm = Permission::findByName('view measuring instruments', 'web');
        $this->standardUser->givePermissionTo($instrumentsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_metrology'));
        $response->assertOk();
        $response->assertSee(route('metrology.instruments'));
        $response->assertDontSee(route('metrology.equipment'));
        $response->assertDontSee(route('metrology.reports'));
        $response->assertDontSee(__('No Accessible Explorers'));
    }

    public function test_financial_dashboard_cards_adhere_to_permissions(): void
    {
        // 1. Super Admin sees all cards
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard_financial'));
        $response->assertOk();
        $response->assertSee(route('financial.warranties'));
        $response->assertSee(route('financial.expenses'));
        $response->assertDontSee(__('No Accessible Explorers'));

        // 2. User with only 'view financial' sees fallback empty state
        $financialPerm = Permission::findByName('view financial', 'web');
        $this->standardUser->givePermissionTo($financialPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_financial'));
        $response->assertOk();
        $response->assertSee(__('No Accessible Explorers'));
        $response->assertDontSee(route('financial.warranties'));
        $response->assertDontSee(route('financial.expenses'));

        // 3. User with 'view warranties' sees only warranties card
        $warrantiesPerm = Permission::findByName('view warranties', 'web');
        $this->standardUser->givePermissionTo($warrantiesPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_financial'));
        $response->assertOk();
        $response->assertSee(route('financial.warranties'));
        $response->assertDontSee(route('financial.expenses'));
        $response->assertDontSee(__('No Accessible Explorers'));
    }

    public function test_master_data_dashboard_cards_adhere_to_permissions(): void
    {
        // 1. Super Admin sees all cards
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard_master_data'));
        $response->assertOk();
        $response->assertSee(route('master-data.clients'));
        $response->assertSee(route('master-data.employees'));
        $response->assertSee(route('master-data.sites'));
        $response->assertDontSee(__('No Accessible Explorers'));

        // 2. User with only 'view master data' sees fallback empty state
        $masterDataPerm = Permission::findByName('view master data', 'web');
        $this->standardUser->givePermissionTo($masterDataPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_master_data'));
        $response->assertOk();
        $response->assertSee(__('No Accessible Explorers'));
        $response->assertDontSee(route('master-data.clients'));

        // 3. User with 'view clients' sees only clients card
        $clientsPerm = Permission::findByName('view clients', 'web');
        $this->standardUser->givePermissionTo($clientsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_master_data'));
        $response->assertOk();
        $response->assertSee(route('master-data.clients'));
        $response->assertDontSee(route('master-data.employees'));
        $response->assertDontSee(route('master-data.sites'));
        $response->assertDontSee(__('No Accessible Explorers'));
    }

    public function test_analytics_dashboard_cards_adhere_to_permissions(): void
    {
        // 1. Super Admin sees all cards
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard_analytics'));
        $response->assertOk();
        $response->assertSee(route('analytics.forecasts'));
        $response->assertSee(route('analytics.statistics'));
        $response->assertDontSee(__('No Accessible Explorers'));

        // 2. User with only 'view analytics' sees fallback empty state
        $analyticsPerm = Permission::findByName('view analytics', 'web');
        $this->standardUser->givePermissionTo($analyticsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_analytics'));
        $response->assertOk();
        $response->assertSee(__('No Accessible Explorers'));
        $response->assertDontSee(route('analytics.forecasts'));
        $response->assertDontSee(route('analytics.statistics'));

        // 3. User with 'view annual forecasts' sees only forecasts card
        $forecastsPerm = Permission::findByName('view annual forecasts', 'web');
        $this->standardUser->givePermissionTo($forecastsPerm);

        $response = $this->actingAs($this->standardUser)->get(route('dashboard_analytics'));
        $response->assertOk();
        $response->assertSee(route('analytics.forecasts'));
        $response->assertDontSee(route('analytics.statistics'));
        $response->assertDontSee(__('No Accessible Explorers'));
    }
}
