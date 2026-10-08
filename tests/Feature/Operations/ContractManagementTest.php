<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Attachment;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $standardUser;

    protected Customer $customer;

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

        $this->customer = Customer::create([
            'company_name' => 'Sonatrach DP',
            'short_name' => 'SH-DP',
            'code' => 'CUST-001',
        ]);
    }

    public function test_super_admin_can_view_contracts_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts'));

        $response->assertOk();
        $response->assertViewIs('operations.contracts.index');
    }

    public function test_super_admin_can_view_contracts_create(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.create'));

        $response->assertOk();
        $response->assertViewIs('operations.contracts.create');
    }

    public function test_super_admin_can_store_contract_with_items(): void
    {
        $payload = [
            'reference' => 'CTR-TEST-2026',
            'object' => 'Fiscal Metering Loop Calibration',
            'date_signature' => '2026-01-15',
            'duree' => 12,
            'montant_global_prevu' => 5000000.00,
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'designation' => 'Gas Chromatograph Calibration',
                    'type' => 'service',
                    'quantity' => 4,
                    'unit_price' => 250000.00,
                    'unit_cost' => 120000.00,
                    'frequency' => 'semestrielle',
                ],
                [
                    'designation' => 'Pressure Transmitter Verification',
                    'type' => 'service',
                    'quantity' => 10,
                    'unit_price' => 50000.00,
                    'unit_cost' => 20000.00,
                    'frequency' => 'annuelle',
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.contracts.store'), $payload);

        $response->assertRedirect(route('operations.contracts'));
        $this->assertDatabaseHas('contracts', [
            'reference' => 'CTR-TEST-2026',
            'customer_id' => $this->customer->id,
        ]);
        $this->assertDatabaseHas('contract_items', [
            'designation' => 'Gas Chromatograph Calibration',
            'quantity' => 4,
        ]);
    }

    public function test_super_admin_can_view_contract_details(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-SHOW-001',
            'object' => 'Show Contract Scope',
            'date_signature' => '2026-02-01',
            'duree' => 6,
            'montant_global_prevu' => 1200000.00,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.show', $contract));

        $response->assertOk();
        $response->assertViewIs('operations.contracts.show');
        $response->assertViewHas('contract');
        $response->assertViewHas('totalPlanned');
        $response->assertViewHas('totalInvoiced');
        $response->assertViewHas('totalConsumed');
        $response->assertSee('CTR-SHOW-001');
    }

    public function test_contract_show_displays_expired_state_when_remain_days_is_negative(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-EXP-001',
            'object' => 'Expired Contract Scope',
            'date_signature' => now()->subMonths(24)->format('Y-m-d'),
            'duree' => 12,
            'montant_global_prevu' => 500000.00,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.show', $contract));

        $response->assertOk();
        $response->assertSee(__('Expired'));
    }

    public function test_super_admin_can_view_contract_edit(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-EDIT-001',
            'object' => 'Edit Contract Scope',
            'date_signature' => '2026-02-01',
            'duree' => 6,
            'montant_global_prevu' => 1200000.00,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.edit', $contract));

        $response->assertOk();
        $response->assertViewIs('operations.contracts.edit');
        $response->assertSee('CTR-EDIT-001');
    }

    public function test_super_admin_can_update_contract(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-ORIG-001',
            'object' => 'Original Scope',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 2000000.00,
            'customer_id' => $this->customer->id,
        ]);

        $item = $contract->items()->create([
            'designation' => 'Initial Item',
            'quantity' => 2,
            'unit_price' => 100000.00,
        ]);

        $payload = [
            'reference' => 'CTR-ORIG-001-MOD',
            'object' => 'Modified Scope',
            'date_signature' => '2026-01-01',
            'duree' => 24,
            'montant_global_prevu' => 3000000.00,
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'id' => $item->id,
                    'designation' => 'Updated Item',
                    'quantity' => 3,
                    'unit_price' => 150000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->put(route('operations.contracts.update', $contract), $payload);

        $response->assertRedirect(route('operations.contracts.show', $contract));
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'reference' => 'CTR-ORIG-001-MOD',
            'duree' => 24,
        ]);
        $this->assertDatabaseHas('contract_items', [
            'id' => $item->id,
            'designation' => 'Updated Item',
            'quantity' => 3,
        ]);
    }

    public function test_super_admin_can_view_contract_statistics(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-STATS-001',
            'object' => 'Stats Contract',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 10000000.00,
            'customer_id' => $this->customer->id,
        ]);

        $contract->items()->create([
            'designation' => 'Flow Computer Verification',
            'quantity' => 5,
            'unit_price' => 200000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.statistics', $contract));

        $response->assertOk();
        $response->assertViewIs('operations.contracts.statistics');
        $response->assertSee('CTR-STATS-001');
    }

    public function test_super_admin_can_delete_contract(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-DEL-001',
            'object' => 'To Delete',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 1000000.00,
            'customer_id' => $this->customer->id,
        ]);

        $contract->items()->create([
            'designation' => 'Item To Delete',
            'quantity' => 1,
            'unit_price' => 50000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('operations.contracts.destroy', $contract));

        $response->assertRedirect(route('operations.contracts'));
        $this->assertDatabaseMissing('contracts', ['id' => $contract->id]);
        $this->assertDatabaseMissing('contract_items', ['contract_id' => $contract->id]);
    }

    public function test_unauthorized_user_cannot_access_contracts(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('operations.contracts'));
        $response->assertForbidden();

        $response = $this->actingAs($this->standardUser)->get(route('operations.contracts.create'));
        $response->assertForbidden();
    }

    public function test_super_admin_can_store_and_link_contract_with_garantie_id(): void
    {
        $warranty = Warranty::create([
            'reference' => 'GAR-TEST-2026',
            'amount' => 350000.00,
            'bank_name' => 'BNA',
            'status' => 'active',
            'type' => 'performance',
        ]);

        $payload = [
            'reference' => 'CTR-WITH-GARANTIE',
            'object' => 'Metering Calibration with Bond',
            'date_signature' => '2026-02-01',
            'duree' => 24,
            'montant_global_prevu' => 3500000.00,
            'customer_id' => $this->customer->id,
            'garantie_id' => $warranty->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.contracts.store'), $payload);

        $response->assertRedirect(route('operations.contracts'));
        $this->assertDatabaseHas('contracts', [
            'reference' => 'CTR-WITH-GARANTIE',
            'garantie_id' => $warranty->id,
        ]);

        $contract = Contract::where('reference', 'CTR-WITH-GARANTIE')->firstOrFail();
        $this->assertEquals($warranty->id, $contract->warranty?->id);
        $this->assertEquals($warranty->id, $contract->garantie?->id);
        $this->assertTrue($warranty->contracts->contains('id', $contract->id));
    }

    public function test_contract_store_supports_legacy_warranty_id_alias(): void
    {
        $warranty = Warranty::create([
            'reference' => 'GAR-ALIAS-2026',
            'amount' => 150000.00,
            'bank_name' => 'BEA',
            'status' => 'active',
            'type' => 'performance',
        ]);

        $payload = [
            'reference' => 'CTR-LEGACY-ALIAS',
            'customer_id' => $this->customer->id,
            'warranty_id' => $warranty->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('operations.contracts.store'), $payload);

        $response->assertRedirect(route('operations.contracts'));
        $this->assertDatabaseHas('contracts', [
            'reference' => 'CTR-LEGACY-ALIAS',
            'garantie_id' => $warranty->id,
        ]);
    }

    public function test_deleting_warranty_nullifies_contract_garantie_id(): void
    {
        $warranty = Warranty::create([
            'reference' => 'GAR-DELETE-TEST',
            'amount' => 200000.00,
            'bank_name' => 'CPA',
            'status' => 'active',
            'type' => 'performance',
        ]);

        $contract = Contract::create([
            'reference' => 'CTR-GAR-DEL',
            'customer_id' => $this->customer->id,
            'garantie_id' => $warranty->id,
        ]);

        $this->assertEquals($warranty->id, $contract->fresh()->garantie_id);

        $warranty->delete();

        $this->assertNull($contract->fresh()->garantie_id);
    }

    public function test_contract_show_displays_services_and_supplies_kpi_cards(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-NATURES-001',
            'object' => 'Testing Services & Supplies Breakdown',
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 5000000.00,
            'customer_id' => $this->customer->id,
        ]);

        // 2 Service items: 2 * 100,000 = 200,000 + 1 * 300,000 = 300,000 => Total Services = 500,000.00 DA
        $contract->items()->create([
            'designation' => 'Calibration Service A',
            'quantity' => 2,
            'unit_price' => 100000.00,
            'type' => 'service',
        ]);
        $contract->items()->create([
            'designation' => 'Inspection Service B',
            'quantity' => 1,
            'unit_price' => 300000.00,
            'type' => 'service',
        ]);

        // 1 Supply item: 4 * 50,000 = 200,000.00 DA
        $contract->items()->create([
            'designation' => 'Pressure Sensor Supply',
            'quantity' => 4,
            'unit_price' => 50000.00,
            'type' => 'supply',
        ]);

        $contract->load('items');

        $this->assertEquals(2, $contract->servicesCount());
        $this->assertEquals(1, $contract->suppliesCount());
        $this->assertEquals('500 000.00 DA', $contract->servicesTotalPlanned());
        $this->assertEquals('200 000.00 DA', $contract->suppliesTotalPlanned());

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.show', $contract));

        $response->assertOk();
        $response->assertSee('500 000.00 DA');
        $response->assertSee('200 000.00 DA');
        $response->assertSee(__('Services'));
        $response->assertSee(__('Supplies'));
        $response->assertSee(__('Unconsumed Value'));
        $response->assertSee('700 000.00 DA');
    }

    public function test_contract_show_displays_unconsumed_value_calculation(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-UNCONSUMED-TEST',
            'object' => 'Unconsumed Testing',
            'customer_id' => $this->customer->id,
        ]);

        // Item 1: 10 * 100,000 = 1,000,000 DA
        $item = $contract->items()->create([
            'designation' => 'Wellhead Maintenance',
            'quantity' => 10,
            'unit_price' => 100000.00,
            'type' => 'service',
        ]);

        $contract->load('items');

        // Without attachments: Unconsumed = 1,000,000.00 DA
        $this->assertEquals('1 000 000.00 DA', $contract->totalUnconsumed());

        // Create attachment consuming 3 units = 300,000.00 DA
        $attachment = Attachment::create([
            'code_ref' => 'ATT-UNCON-001',
            'status' => 'draft',
            'type' => 'service',
        ]);

        $attachment->items()->create([
            'contract_item_id' => $item->id,
            'actual_quantity' => 3,
        ]);

        // Unconsumed = 1,000,000 - 300,000 = 700,000.00 DA
        $this->assertEquals(700000.00, $contract->rawTotalUnconsumed());
        $this->assertEquals('700 000.00 DA', $contract->totalUnconsumed());

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.show', $contract));

        $response->assertOk();
        $response->assertSee(__('Unconsumed Value'));
        $response->assertSee('700 000.00 DA');
        $response->assertSee('300 000.00 DA'); // Total consumed
        $response->assertSee('1 000 000.00 DA'); // Items sum
    }

    public function test_contract_show_orders_items_service_first_then_supply_second(): void
    {
        $contract = Contract::create([
            'reference' => 'CTR-ORDER-TEST',
            'object' => 'Items Ordering Test',
            'customer_id' => $this->customer->id,
        ]);

        // Create Supply item FIRST (lower ID)
        $supply = $contract->items()->create([
            'designation' => 'First Supply Item',
            'quantity' => 1,
            'unit_price' => 10000.00,
            'type' => 'supply',
        ]);

        // Create Service item SECOND (higher ID)
        $service = $contract->items()->create([
            'designation' => 'Second Service Item',
            'quantity' => 1,
            'unit_price' => 20000.00,
            'type' => 'service',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.contracts.show', $contract));

        $response->assertOk();
        $viewContract = $response->viewData('contract');

        $this->assertCount(2, $viewContract->items);
        $this->assertEquals($service->id, $viewContract->items->first()->id);
        $this->assertEquals('service', $viewContract->items->first()->type);
        $this->assertEquals($supply->id, $viewContract->items->last()->id);
        $this->assertEquals('supply', $viewContract->items->last()->type);
    }
}
