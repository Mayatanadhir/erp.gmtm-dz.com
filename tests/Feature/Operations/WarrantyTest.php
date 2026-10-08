<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Enums\WarrantyStatus;
use App\Enums\WarrantyType;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarrantyTest extends TestCase
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
     * 1. Guests are redirected to login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('financial.warranties'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. User without permission cannot view warranties.
     */
    public function test_user_without_permission_cannot_view_warranties(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('financial.warranties'));

        $response->assertForbidden();
    }

    /**
     * 3. Super Admin can view bank guarantees list.
     */
    public function test_super_admin_can_view_warranties_page(): void
    {
        Warranty::create([
            'reference' => 'GRT-2026-001',
            'bank_name' => 'BNA Alger',
            'amount' => 1500000.00,
            'started_at' => '2026-01-15',
            'status' => WarrantyStatus::Active,
            'type' => WarrantyType::Performance,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('financial.warranties'));

        $response->assertOk();
        $response->assertSee('GRT-2026-001');
        $response->assertSee('BNA Alger');
        $response->assertSee('1 500 000.00');
    }

    /**
     * 4. Super Admin can store a new bank guarantee.
     */
    public function test_super_admin_can_store_new_warranty(): void
    {
        $payload = [
            'reference' => 'GRT-2026-NEW',
            'bank_name' => 'BEA',
            'amount' => 250000.00,
            'started_at' => '2026-03-01',
            'status' => WarrantyStatus::Active->value,
            'type' => WarrantyType::BidBond->value,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('financial.warranties.store'), $payload);

        $response->assertRedirect(route('financial.warranties'));
        $response->assertSessionHas('success');

        $pageResponse = $this->actingAs($this->superAdmin)
            ->withSession(['success' => __('Bank guarantee created successfully.')])
            ->get(route('financial.warranties'));
        $pageResponse->assertSee(__('Bank guarantee created successfully.'));

        $this->assertDatabaseHas('garanties', [
            'reference' => 'GRT-2026-NEW',
            'bank_name' => 'BEA',
            'amount' => 250000.00,
        ]);
    }

    /**
     * 5. Validation fails on duplicate reference.
     */
    public function test_store_validation_fails_on_duplicate_reference(): void
    {
        Warranty::create([
            'reference' => 'GRT-EXISTING',
            'bank_name' => 'BNA',
            'amount' => 100000,
            'status' => WarrantyStatus::Active,
            'type' => WarrantyType::Other,
        ]);

        $payload = [
            'reference' => 'GRT-EXISTING',
            'bank_name' => 'CPA',
            'amount' => 200000,
            'status' => WarrantyStatus::Active->value,
            'type' => WarrantyType::Retention->value,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('financial.warranties.store'), $payload);

        $response->assertSessionHasErrors(['reference']);
    }

    /**
     * 6. Super Admin can update an existing bank guarantee.
     */
    public function test_super_admin_can_update_warranty(): void
    {
        $warranty = Warranty::create([
            'reference' => 'GRT-ORIGINAL',
            'bank_name' => 'BNA',
            'amount' => 50000,
            'status' => WarrantyStatus::Active,
            'type' => WarrantyType::BidBond,
        ]);

        $payload = [
            'reference' => 'GRT-UPDATED',
            'bank_name' => 'BADR Bank',
            'amount' => 75000.50,
            'status' => WarrantyStatus::Released->value,
            'type' => WarrantyType::Performance->value,
        ];

        $response = $this->actingAs($this->superAdmin)->put(
            route('financial.warranties.update', $warranty),
            $payload
        );

        $response->assertRedirect(route('financial.warranties'));
        $this->assertDatabaseHas('garanties', [
            'id' => $warranty->id,
            'reference' => 'GRT-UPDATED',
            'bank_name' => 'BADR Bank',
            'status' => 'released',
        ]);
    }

    /**
     * 7. Super Admin can delete a bank guarantee.
     */
    public function test_super_admin_can_delete_warranty(): void
    {
        $warranty = Warranty::create([
            'reference' => 'GRT-TO-DELETE',
            'bank_name' => 'BNA',
            'amount' => 10000,
            'status' => WarrantyStatus::Expired,
            'type' => WarrantyType::Other,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(
            route('financial.warranties.destroy', $warranty)
        );

        $response->assertRedirect(route('financial.warranties'));
        $this->assertDatabaseMissing('garanties', [
            'id' => $warranty->id,
        ]);
    }

    /**
     * 8. Active bank guarantees are ordered first.
     */
    public function test_active_warranties_are_ordered_first(): void
    {
        // Create an expired warranty first with older id
        $expired = Warranty::create([
            'reference' => 'GRT-EXPIRED-FIRST',
            'bank_name' => 'BNA',
            'amount' => 100000,
            'started_at' => '2025-01-01',
            'status' => WarrantyStatus::Expired,
            'type' => WarrantyType::Other,
        ]);

        // Create an active warranty with later id
        $active = Warranty::create([
            'reference' => 'GRT-ACTIVE-LATER',
            'bank_name' => 'BEA',
            'amount' => 200000,
            'started_at' => '2026-01-01',
            'status' => WarrantyStatus::Active,
            'type' => WarrantyType::Performance,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('financial.warranties'));

        $response->assertOk();
        $warranties = $response->viewData('warranties');
        $this->assertEquals($active->id, $warranties->first()->id);
    }

    /**
     * 9. Legacy operations.warranties routes redirect to financial.warranties.
     */
    public function test_legacy_operations_warranties_route_redirects_to_financial_warranties(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('operations.warranties'));

        $response->assertRedirect(route('financial.warranties'));
    }

    /**
     * 10. Legacy operations.warranties.store redirects to financial.warranties.
     */
    public function test_legacy_operations_store_warranty_redirects_to_financial_warranties(): void
    {
        $payload = [
            'reference' => 'GRT-LEGACY-OP',
            'bank_name' => 'BNA',
            'amount' => 120000.00,
            'status' => WarrantyStatus::Active->value,
            'type' => WarrantyType::Performance->value,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.warranties.store'), $payload);

        $response->assertRedirect(route('financial.warranties'));
        $this->assertDatabaseHas('garanties', ['reference' => 'GRT-LEGACY-OP']);
    }
}
