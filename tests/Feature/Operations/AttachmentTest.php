<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Enums\MissionStatus;
use App\Models\Attachment;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\ItemType;
use App\Models\Mission;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Customer $customer;

    protected Site $site;

    protected Contract $contract;

    protected ContractItem $contractItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->customer = Customer::create([
            'company_name' => 'Sonatrach DP',
            'short_name' => 'SH-DP',
        ]);

        $this->site = Site::create([
            'customer_id' => $this->customer->id,
            'full_name' => 'Hassi Messaoud Production',
            'short_name' => 'HMD',
        ]);

        $this->contract = Contract::create([
            'reference' => 'CTR-2026-001',
            'object' => 'Maintenance contract',
            'customer_id' => $this->customer->id,
            'date_signature' => now()->subMonth(),
            'duree' => 12,
            'montant_global_prevu' => 5000000,
        ]);

        $itemType = ItemType::create([
            'designation' => 'Etalonnage debimetre',
        ]);

        $this->contractItem = ContractItem::create([
            'contract_id' => $this->contract->id,
            'item_type_id' => $itemType->id,
            'designation' => 'Calibration Services',
            'type' => 'service',
            'quantity' => 10,
            'unit_price' => 50000,
        ]);
    }

    public function test_create_attachment_view_loads_planned_missions_via_customer_site(): void
    {
        // Mission with contract_id = null, but linked to customer site, status = planned
        $mission = Mission::create([
            'reference' => 'M-2026-999',
            'site_id' => $this->site->id,
            'contract_id' => null,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.attachments.create'));

        $response->assertOk();
        $response->assertSee('CTR-2026-001');
        $response->assertSee('M-2026-999');
    }

    public function test_create_attachment_view_excludes_expired_contracts(): void
    {
        $expiredContract = Contract::create([
            'reference' => 'CTR-EXPIRED-999',
            'object' => 'Old expired contract',
            'customer_id' => $this->customer->id,
            'date_signature' => now()->subMonths(24),
            'duree' => 12,
            'montant_global_prevu' => 1000000,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.attachments.create'));

        $response->assertOk();
        $response->assertSee($this->contract->reference);
        $response->assertDontSee($expiredContract->reference);

        $loadedContracts = $response->viewData('contracts');
        $this->assertTrue($loadedContracts->contains('id', $this->contract->id));
        $this->assertFalse($loadedContracts->contains('id', $expiredContract->id));
    }

    public function test_edit_attachment_view_retains_completed_mission(): void
    {
        $mission = Mission::create([
            'reference' => 'M-2026-888',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Completed,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5),
        ]);

        $attachment = Attachment::create([
            'mission_id' => $mission->id,
            'date' => now()->subDays(2),
            'code_ref' => 'ATT-2026-888',
            'type' => 'service',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.attachments.edit', $attachment));

        $response->assertOk();
        $response->assertSee('M-2026-888');
    }

    public function test_store_attachment_updates_mission_contract_id_if_null(): void
    {
        $mission = Mission::create([
            'reference' => 'M-2026-777',
            'site_id' => $this->site->id,
            'contract_id' => null,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.attachments.store'), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                [
                    'contract_item_id' => $this->contractItem->id,
                    'actual_quantity' => 2,
                    'planned_quantity' => 2,
                ],
            ],
        ]);

        $response->assertRedirect(route('operations.attachments'));
        $this->assertDatabaseHas('attachments', [
            'mission_id' => $mission->id,
            'status' => 'draft',
        ]);

        // Assert mission contract_id got updated
        $this->assertEquals($this->contract->id, $mission->fresh()->contract_id);
    }
}
