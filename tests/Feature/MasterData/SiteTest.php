<?php

declare(strict_types=1);

namespace Tests\Feature\MasterData;

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteTest extends TestCase
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

    /**
     * 1. Guests are redirected to the login page.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('master-data.sites'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. User without permission cannot view sites (403 Forbidden).
     */
    public function test_user_without_permission_cannot_view_sites(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('master-data.sites'));

        $response->assertForbidden();
    }

    /**
     * 3. User with permission can view sites.
     */
    public function test_user_with_permission_can_view_sites(): void
    {
        $this->standardUser->givePermissionTo('view sites');

        $customer = Customer::factory()->create(['company_name' => 'Sonatrach DP']);
        $site = Site::factory()->create([
            'customer_id' => $customer->id,
            'site_code' => 'ST-001',
            'full_name' => 'Hassi Messaoud Station',
        ]);

        $response = $this->actingAs($this->standardUser)->get(route('master-data.sites'));

        $response->assertOk();
        $response->assertSee('ST-001');
        $response->assertSee('Hassi Messaoud Station');
        $response->assertSee('Sonatrach DP');
    }

    /**
     * 4. Sites can be searched by keyword.
     */
    public function test_sites_can_be_filtered_by_search_keyword(): void
    {
        $this->standardUser->givePermissionTo('view sites');

        $siteMatch = Site::factory()->create([
            'site_code' => 'SPECIAL-CODE-99',
            'full_name' => 'Adrar Solar Field',
            'location' => 'Adrar Desert',
        ]);

        $siteOther = Site::factory()->create([
            'site_code' => 'OTHER-01',
            'full_name' => 'Skikda Refinery',
            'location' => 'Skikda Port',
        ]);

        $response = $this->actingAs($this->standardUser)->get(route('master-data.sites', ['search' => 'SPECIAL-CODE-99']));

        $response->assertOk();
        $response->assertSee('Adrar Solar Field');
        $response->assertDontSee('Skikda Refinery');
    }

    /**
     * 5. Sites can be filtered by customer_id.
     */
    public function test_sites_can_be_filtered_by_customer_id(): void
    {
        $this->standardUser->givePermissionTo('view sites');

        $custA = Customer::factory()->create(['company_name' => 'Customer Alpha']);
        $custB = Customer::factory()->create(['company_name' => 'Customer Beta']);

        $siteA = Site::factory()->create([
            'customer_id' => $custA->id,
            'full_name' => 'Alpha Terminal',
        ]);

        $siteB = Site::factory()->create([
            'customer_id' => $custB->id,
            'full_name' => 'Beta Terminal',
        ]);

        $response = $this->actingAs($this->standardUser)->get(route('master-data.sites', ['customer_id' => $custA->id]));

        $response->assertOk();
        $response->assertSee('Alpha Terminal');
        $response->assertDontSee('Beta Terminal');
    }

    /**
     * 6. User with permission can create a site.
     */
    public function test_user_with_permission_can_create_site(): void
    {
        $this->standardUser->givePermissionTo(['view sites', 'create sites']);

        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'site_code' => 'SIT-NEW-01',
            'full_name' => 'In Amenas CPF Plant',
            'short_name' => 'IACPF',
            'location' => 'Illizi, Algeria',
            'map_link' => 'https://maps.app.goo.gl/exampleLocation123',
        ];

        $response = $this->actingAs($this->standardUser)->post(route('master-data.sites.store'), $payload);

        $response->assertRedirect(route('master-data.sites'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sites', [
            'site_code' => 'SIT-NEW-01',
            'full_name' => 'In Amenas CPF Plant',
            'short_name' => 'IACPF',
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * 7. User with permission can update a site.
     */
    public function test_user_with_permission_can_update_site(): void
    {
        $this->standardUser->givePermissionTo(['view sites', 'edit sites']);

        $site = Site::factory()->create([
            'site_code' => 'OLD-CODE-01',
            'full_name' => 'Old Site Name',
        ]);

        $payload = [
            'site_code' => 'NEW-CODE-02',
            'full_name' => 'Updated Site Name',
            'short_name' => 'UPD',
            'location' => 'Ouargla',
            'map_link' => 'https://maps.app.goo.gl/updated',
        ];

        $response = $this->actingAs($this->standardUser)->put(route('master-data.sites.update', $site), $payload);

        $response->assertRedirect(route('master-data.sites'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'site_code' => 'NEW-CODE-02',
            'full_name' => 'Updated Site Name',
            'location' => 'Ouargla',
        ]);
    }

    /**
     * 8. User with permission can delete a site.
     */
    public function test_user_with_permission_can_delete_site(): void
    {
        $this->standardUser->givePermissionTo(['view sites', 'delete sites']);

        $site = Site::factory()->create([
            'full_name' => 'Site to be deleted',
        ]);

        $response = $this->actingAs($this->standardUser)->delete(route('master-data.sites.destroy', $site));

        $response->assertRedirect(route('master-data.sites'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('sites', [
            'id' => $site->id,
        ]);
    }

    /**
     * 9. Super Admin bypasses all permissions.
     */
    public function test_super_admin_bypasses_all_site_permissions(): void
    {
        $site = Site::factory()->create([
            'full_name' => 'Sovereign View Site',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('master-data.sites'));
        $response->assertOk();
        $response->assertSee('Sovereign View Site');

        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('master-data.sites.destroy', $site));
        $deleteResponse->assertRedirect(route('master-data.sites'));

        $this->assertSoftDeleted('sites', [
            'id' => $site->id,
        ]);
    }

    /**
     * 10. Legacy sites migration imports records accurately with original IDs.
     */
    public function test_legacy_sites_can_be_imported_preserving_ids(): void
    {
        // First seed customers so foreign keys match
        $this->artisan('customers:import-legacy')->assertSuccessful();

        $this->artisan('sites:import-legacy')->assertSuccessful();

        $this->assertDatabaseHas('sites', [
            'id' => 1,
            'customer_id' => 15,
            'site_code' => 'GTIM-001',
            'short_name' => 'GTIM',
        ]);

        $this->assertDatabaseHas('sites', [
            'id' => 4,
            'customer_id' => 16,
            'site_code' => 'HTJ-004',
            'short_name' => 'HTJ',
        ]);

        $this->assertDatabaseHas('sites', [
            'id' => 13,
            'customer_id' => 18,
            'site_code' => 'STAH-013',
            'short_name' => 'STAH',
        ]);

        $this->assertDatabaseHas('sites', [
            'id' => 14,
            'customer_id' => 17,
            'site_code' => 'GTFT',
            'short_name' => 'G-TFT',
        ]);
    }
}
