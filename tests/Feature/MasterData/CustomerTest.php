<?php

declare(strict_types=1);

namespace Tests\Feature\MasterData;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerTest extends TestCase
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
        $response = $this->get(route('master-data.clients'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. User without permission cannot view clients (403 Forbidden).
     */
    public function test_user_without_permission_cannot_view_clients(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('master-data.clients'));

        $response->assertForbidden();
    }

    /**
     * 3. User with permission can view clients list.
     */
    public function test_user_with_permission_can_view_clients_list(): void
    {
        $this->standardUser->givePermissionTo('view clients');

        $customer = Customer::factory()->create([
            'company_name' => 'Sonatrach Exploration & Production',
            'reference' => 'CLI-2026-001',
            'registration_number' => '16/00-0987654B20',
        ]);

        $response = $this->actingAs($this->standardUser)->get(route('master-data.clients'));

        $response->assertOk();
        $response->assertSee('Sonatrach Exploration & Production');
        $response->assertSee('CLI-2026-001');
        $response->assertSee('16/00-0987654B20');
    }

    /**
     * 4. User can search clients by company name or reference.
     */
    public function test_user_can_search_clients(): void
    {
        $this->standardUser->givePermissionTo('view clients');

        Customer::factory()->create([
            'company_name' => 'Air Algerie Cargo',
            'reference' => 'CLI-AIR-01',
        ]);

        Customer::factory()->create([
            'company_name' => 'Cosider Construction',
            'reference' => 'CLI-COS-02',
        ]);

        $response = $this->actingAs($this->standardUser)->get(route('master-data.clients', ['search' => 'Cosider']));

        $response->assertOk();
        $response->assertSee('Cosider Construction');
        $response->assertDontSee('Air Algerie Cargo');
    }

    /**
     * 5. User with permission can create customer.
     */
    public function test_user_with_permission_can_create_customer(): void
    {
        $this->standardUser->givePermissionTo(['view clients', 'create clients']);

        $payload = [
            'company_name' => 'SARL GMTM Partner',
            'short_name' => 'GMTM-P',
            'reference' => 'CLI-2026-777',
            'registration_number' => 'RC-99887766',
            'phone' => '+213 21 00 11 22',
            'email' => 'partner@gmtm-dz.com',
            'website' => 'https://partner.gmtm-dz.com',
            'address' => 'Zone Industrielle, Rouiba, Alger',
            'notes' => 'Key enterprise customer for maintenance services.',
        ];

        $response = $this->actingAs($this->standardUser)->post(route('master-data.clients.store'), $payload);

        $response->assertRedirect(route('master-data.clients'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'company_name' => 'SARL GMTM Partner',
            'reference' => 'CLI-2026-777',
            'email' => 'partner@gmtm-dz.com',
        ]);
    }

    /**
     * 6. Customer creation validates required company name and unique reference.
     */
    public function test_customer_creation_validation_rules(): void
    {
        $this->standardUser->givePermissionTo(['view clients', 'create clients']);

        Customer::factory()->create([
            'reference' => 'CLI-EXISTING',
        ]);

        $response = $this->actingAs($this->standardUser)->post(route('master-data.clients.store'), [
            'company_name' => '',
            'reference' => 'CLI-EXISTING',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['company_name', 'reference', 'email']);
    }

    /**
     * 7. User with permission can update customer.
     */
    public function test_user_with_permission_can_update_customer(): void
    {
        $this->standardUser->givePermissionTo(['view clients', 'edit clients']);

        $customer = Customer::factory()->create([
            'company_name' => 'Original Name Spa',
            'short_name' => 'OLD',
            'reference' => 'CLI-001',
        ]);

        $payload = [
            'company_name' => 'Updated Company Name Spa',
            'short_name' => 'NEW',
            'reference' => 'CLI-001', // Keeps same reference without error
            'phone' => '+213 555 12 34 56',
            'email' => 'updated@company.dz',
            'website' => 'https://company.dz',
            'address' => 'New Address, Oran',
            'registration_number' => '16/00-999999',
            'notes' => 'Updated notes.',
        ];

        $response = $this->actingAs($this->standardUser)->put(route('master-data.clients.update', $customer), $payload);

        $response->assertRedirect(route('master-data.clients'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'Updated Company Name Spa',
            'short_name' => 'NEW',
            'email' => 'updated@company.dz',
        ]);
    }

    /**
     * 8. User with permission can delete customer.
     */
    public function test_user_with_permission_can_delete_customer(): void
    {
        $this->standardUser->givePermissionTo(['view clients', 'delete clients']);

        $customer = Customer::factory()->create([
            'company_name' => 'Customer to Delete',
        ]);

        $response = $this->actingAs($this->standardUser)->delete(route('master-data.clients.destroy', $customer));

        $response->assertRedirect(route('master-data.clients'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
        $this->assertNull(Customer::find($customer->id));
    }

    /**
     * 9. Super Admin bypasses all client permissions.
     */
    public function test_super_admin_bypasses_all_client_permissions(): void
    {
        $customer = Customer::factory()->create([
            'company_name' => 'Super Admin Customer View',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('master-data.clients'));
        $response->assertOk();
        $response->assertSee('Super Admin Customer View');

        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('master-data.clients.destroy', $customer));
        $deleteResponse->assertRedirect(route('master-data.clients'));

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
        $this->assertNull(Customer::find($customer->id));
    }

    /**
     * 10. Legacy customer migration imports records accurately with original IDs.
     */
    public function test_legacy_customers_can_be_imported_preserving_ids(): void
    {
        $this->artisan('customers:import-legacy')
            ->assertSuccessful();

        $this->assertDatabaseHas('customers', [
            'id' => 15,
            'reference' => 'GTIM',
            'company_name' => 'GROUPEMENT - TIMIMOUN',
            'short_name' => 'GTIM',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => 16,
            'reference' => 'SH-DP-ADR',
            'company_name' => 'SONATRACH - DP - ADR',
            'short_name' => 'SH-DP-ADR',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => 17,
            'reference' => 'GTFT',
            'company_name' => 'GROUPEMENT - TFT',
            'short_name' => 'GTFT',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => 18,
            'reference' => 'SH/DP/STAH',
            'company_name' => "SONATRACH - CPF d'ALRAR - STAH",
            'short_name' => 'SH/DP/STAH',
        ]);
    }
}
