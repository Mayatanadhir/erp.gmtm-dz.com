<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Enums\ExpenseAffiliation;
use App\Enums\MissionStatus;
use App\Models\Attachment;
use App\Models\AttachmentItem;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Expense;
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

    public function test_store_attachment_rejects_empty_items_when_all_quantities_are_zero(): void
    {
        $mission = Mission::create([
            'reference' => 'M-ZERO-QTY',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
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
                    'actual_quantity' => 0,
                    'planned_quantity' => 0,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('attachments', ['mission_id' => $mission->id]);
    }

    public function test_store_attachment_rejects_contract_items_belonging_to_another_contract(): void
    {
        $otherContract = Contract::create([
            'reference' => 'CTR-OTHER-999',
            'object' => 'Other contract',
            'customer_id' => $this->customer->id,
            'date_signature' => now(),
            'duree' => 12,
        ]);

        $otherItem = ContractItem::create([
            'contract_id' => $otherContract->id,
            'designation' => 'Foreign Service Item',
            'type' => 'service',
            'quantity' => 5,
            'unit_price' => 10000,
        ]);

        $mission = Mission::create([
            'reference' => 'M-CROSS-CTR',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.attachments.store'), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                [
                    'contract_item_id' => $otherItem->id,
                    'actual_quantity' => 1,
                    'planned_quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('attachments', ['mission_id' => $mission->id]);
    }

    public function test_update_attachment_syncs_existing_items_in_place_preserving_primary_ids(): void
    {
        $mission = Mission::create([
            'reference' => 'M-SYNC-TEST',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $attachment = Attachment::create([
            'mission_id' => $mission->id,
            'date' => now()->toDateString(),
            'code_ref' => 'ATT-SYNC-001',
            'type' => 'service',
            'status' => 'draft',
        ]);

        $initialItem = AttachmentItem::create([
            'attachment_id' => $attachment->id,
            'contract_item_id' => $this->contractItem->id,
            'actual_quantity' => 2,
            'planned_quantity' => 2,
        ]);

        $initialItemId = $initialItem->id;

        $response = $this->actingAs($this->superAdmin)->put(route('operations.attachments.update', $attachment), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                [
                    'contract_item_id' => $this->contractItem->id,
                    'actual_quantity' => 5,
                    'planned_quantity' => 5,
                ],
            ],
        ]);

        $response->assertRedirect(route('operations.attachments.show', $attachment->id));

        $initialItem->refresh();
        $this->assertSame($initialItemId, $initialItem->id);
        $this->assertEquals(5.00, (float) $initialItem->actual_quantity);
    }

    public function test_update_attachment_preserves_expense_charges_links(): void
    {
        $mission = Mission::create([
            'reference' => 'M-EXP-LINK',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $attachment = Attachment::create([
            'mission_id' => $mission->id,
            'date' => now()->toDateString(),
            'code_ref' => 'ATT-EXP-001',
            'type' => 'service',
            'status' => 'draft',
        ]);

        $item = AttachmentItem::create([
            'attachment_id' => $attachment->id,
            'contract_item_id' => $this->contractItem->id,
            'actual_quantity' => 2,
            'planned_quantity' => 2,
        ]);

        $expense = Expense::create([
            'amount' => 5000.00,
            'date' => now()->toDateString(),
            'description' => 'Field testing charge',
            'type' => ExpenseAffiliation::Mission,
            'attachment_item_id' => $item->id,
        ]);

        $this->assertSame($item->id, $expense->attachment_item_id);

        $this->actingAs($this->superAdmin)->put(route('operations.attachments.update', $attachment), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                [
                    'contract_item_id' => $this->contractItem->id,
                    'actual_quantity' => 3,
                    'planned_quantity' => 3,
                ],
            ],
        ]);

        $expense->refresh();
        $this->assertSame($item->id, $expense->attachment_item_id, 'Expense link must not be broken during attachment update');
    }

    public function test_ods_and_code_ref_validation_rejects_over_45_characters(): void
    {
        $mission = Mission::create([
            'reference' => 'M-LEN-TEST',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $longString = str_repeat('A', 46);

        $response = $this->actingAs($this->superAdmin)->post(route('operations.attachments.store'), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'ods' => $longString,
            'code_ref' => $longString,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                [
                    'contract_item_id' => $this->contractItem->id,
                    'actual_quantity' => 1,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['ods', 'code_ref']);
    }

    public function test_create_view_excludes_missions_belonging_to_other_contracts(): void
    {
        $otherContract = Contract::create([
            'reference' => 'CTR-OTHER-MIS',
            'object' => 'Other Contract',
            'customer_id' => $this->customer->id,
            'date_signature' => now(),
            'duree' => 12,
        ]);

        $missionOnOtherContract = Mission::create([
            'reference' => 'M-ON-OTHER-CTR',
            'site_id' => $this->site->id,
            'contract_id' => $otherContract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.attachments.create'));
        $response->assertOk();

        $loadedContracts = $response->viewData('contracts');
        $firstContract = $loadedContracts->firstWhere('id', $this->contract->id);

        $this->assertNotNull($firstContract);
        $this->assertFalse(
            $firstContract->missions->contains('id', $missionOnOtherContract->id),
            'Missions belonging to other contracts must not leak into another contract dropdown'
        );
    }

    public function test_cannot_edit_or_delete_approved_attachment(): void
    {
        $mission = Mission::create([
            'reference' => 'M-APPROVED-GUARD',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Completed,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(2),
        ]);

        $attachment = Attachment::create([
            'mission_id' => $mission->id,
            'date' => now()->toDateString(),
            'code_ref' => 'ATT-APP-001',
            'type' => 'service',
            'status' => 'approved',
        ]);

        // Attempt to edit view
        $editResponse = $this->actingAs($this->superAdmin)->get(route('operations.attachments.edit', $attachment));
        $editResponse->assertRedirect(route('operations.attachments.show', $attachment->id));
        $editResponse->assertSessionHas('warning');

        // Attempt to update
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('operations.attachments.update', $attachment), [
            'date' => now()->toDateString(),
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'approved',
            'items' => [
                [
                    'contract_item_id' => $this->contractItem->id,
                    'actual_quantity' => 10,
                ],
            ],
        ]);
        $updateResponse->assertSessionHasErrors('status');

        // Attempt to delete
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('operations.attachments.destroy', $attachment));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_cannot_delete_attachment_when_line_items_have_linked_charges(): void
    {
        $mission = Mission::create([
            'reference' => 'M-DEL-EXP-GUARD',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $attachment = Attachment::create([
            'mission_id' => $mission->id,
            'date' => now()->toDateString(),
            'code_ref' => 'ATT-DEL-CHG-001',
            'type' => 'service',
            'status' => 'draft',
        ]);

        $item = AttachmentItem::create([
            'attachment_id' => $attachment->id,
            'contract_item_id' => $this->contractItem->id,
            'actual_quantity' => 2,
        ]);

        Expense::create([
            'amount' => 12000.00,
            'date' => now()->toDateString(),
            'description' => 'Linked charge',
            'type' => ExpenseAffiliation::Mission,
            'attachment_item_id' => $item->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('operations.attachments.destroy', $attachment));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_code_ref_auto_generation_is_sequential_and_concurrency_safe(): void
    {
        $mission = Mission::create([
            'reference' => 'M-SEQ-001',
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'status' => MissionStatus::Planned,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        $res1 = $this->actingAs($this->superAdmin)->post(route('operations.attachments.store'), [
            'date' => '2026-05-01',
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                ['contract_item_id' => $this->contractItem->id, 'actual_quantity' => 1],
            ],
        ]);
        $this->actingAs($this->superAdmin)->post(route('operations.attachments.store'), [
            'date' => '2026-05-02',
            'contract_id' => $this->contract->id,
            'mission_id' => $mission->id,
            'type' => 'service',
            'status' => 'draft',
            'items' => [
                ['contract_item_id' => $this->contractItem->id, 'actual_quantity' => 1],
            ],
        ]);

        $first = Attachment::whereDate('date', '2026-05-01')->first();
        $second = Attachment::whereDate('date', '2026-05-02')->first();

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotEquals($first->code_ref, $second->code_ref);

        preg_match('/-(\d+)$/', (string) $first->code_ref, $m1);
        preg_match('/-(\d+)$/', (string) $second->code_ref, $m2);

        $this->assertEquals((int) $m1[1] + 1, (int) $m2[1]);
    }
}
