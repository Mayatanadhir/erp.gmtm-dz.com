<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\CalibrationCertificateExtraction;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Grandeur;
use App\Models\ItemType;
use App\Models\Site;
use App\Models\User;
use App\Models\Warranty;
use App\Services\DataPruningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalSoftDeletesStandardizationTest extends TestCase
{
    use RefreshDatabase;

    private DataPruningService $pruningService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pruningService = app(DataPruningService::class);
    }

    public function test_all_standardized_tables_have_nullable_deleted_at_column(): void
    {
        $tables = [
            'customers',
            'sites',
            'contracts',
            'contract_items',
            'attachments',
            'attachment_items',
            'charges',
            'garanties',
            'income_forecasts',
            'reports',
            'chromatograph_verifications',
            'flow_computer_verifications',
            'probe_verifications',
            'prover_verifications',
            'prover_verification_runs',
            'transmitter_verifications',
            'item_types',
            'grandeurs',
            'calibration_certificate_extractions',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'deleted_at'),
                "Table '{$table}' is missing the deleted_at column."
            );
        }
    }

    public function test_customer_lifecycle_soft_delete_restore_and_force_delete(): void
    {
        $customer = Customer::create([
            'reference' => 'CUST-STD-001',
            'company_name' => 'Standardization Corp',
        ]);

        $id = $customer->id;

        // 1. Soft delete
        $customer->delete();
        $this->assertSoftDeleted('customers', ['id' => $id]);
        $this->assertNull(Customer::find($id));
        $this->assertNotNull(Customer::withTrashed()->find($id));

        // 2. Restore
        $customer->restore();
        $this->assertDatabaseHas('customers', ['id' => $id, 'deleted_at' => null]);
        $this->assertNotNull(Customer::find($id));

        // 3. Force Delete
        $customer->forceDelete();
        $this->assertDatabaseMissing('customers', ['id' => $id]);
    }

    public function test_site_lifecycle_soft_delete_and_restore(): void
    {
        $customer = Customer::create([
            'reference' => 'CUST-STD-002',
            'company_name' => 'Site Owner',
        ]);

        $site = Site::create([
            'customer_id' => $customer->id,
            'site_code' => 'SITE-STD-001',
            'full_name' => 'Hassi Messaoud Station',
        ]);

        $id = $site->id;

        $site->delete();
        $this->assertSoftDeleted('sites', ['id' => $id]);
        $this->assertNull(Site::find($id));

        $site->restore();
        $this->assertDatabaseHas('sites', ['id' => $id, 'deleted_at' => null]);
    }

    public function test_contract_and_contract_items_lifecycle_soft_delete(): void
    {
        $customer = Customer::create([
            'reference' => 'CUST-STD-003',
            'company_name' => 'Contract Customer',
        ]);

        $contract = Contract::create([
            'reference' => 'CTR-STD-999',
            'object' => 'Metering Maintenance',
            'customer_id' => $customer->id,
        ]);

        $item = ContractItem::create([
            'contract_id' => $contract->id,
            'designation' => 'Technical Calibration',
            'quantity' => 10,
            'unit_price' => 50000,
        ]);

        $contractId = $contract->id;
        $itemId = $item->id;

        // Soft delete both
        $item->delete();
        $contract->delete();

        $this->assertSoftDeleted('contracts', ['id' => $contractId]);
        $this->assertSoftDeleted('contract_items', ['id' => $itemId]);

        $this->assertNull(Contract::find($contractId));
        $this->assertNull(ContractItem::find($itemId));

        // Verify DataPruningService detects them as trashed tables
        $trashedTables = $this->pruningService->getTablesWithTrashedData();
        $detectedTables = array_column($trashedTables, 'table');

        $this->assertContains('contracts', $detectedTables);
        $this->assertContains('contract_items', $detectedTables);

        // Restore contract
        $contract->restore();
        $this->assertNotNull(Contract::find($contractId));
    }

    public function test_attachment_lifecycle_soft_delete(): void
    {
        $attachment = Attachment::create([
            'code_ref' => 'ATT-STD-001',
            'status' => 'Draft',
        ]);

        $id = $attachment->id;

        $attachment->delete();
        $this->assertSoftDeleted('attachments', ['id' => $id]);
        $this->assertNull(Attachment::find($id));

        $attachment->restore();
        $this->assertNotNull(Attachment::find($id));
    }

    public function test_expense_and_warranty_lifecycle_soft_delete(): void
    {
        $expense = Expense::create([
            'amount' => 15000,
            'date' => now()->toDateString(),
            'description' => 'Field accommodation',
            'type' => 'mission',
        ]);

        $warranty = Warranty::create([
            'reference' => 'WAR-STD-777',
            'bank_name' => 'BNA',
            'amount' => 1000000,
            'status' => 'active',
            'type' => 'bid_bond',
        ]);

        $expId = $expense->id;
        $warId = $warranty->id;

        $expense->delete();
        $warranty->delete();

        $this->assertSoftDeleted('charges', ['id' => $expId]);
        $this->assertSoftDeleted('garanties', ['id' => $warId]);

        $this->assertNull(Expense::find($expId));
        $this->assertNull(Warranty::find($warId));

        $expense->restore();
        $warranty->restore();

        $this->assertNotNull(Expense::find($expId));
        $this->assertNotNull(Warranty::find($warId));
    }

    public function test_catalog_entities_item_types_and_grandeurs_soft_delete(): void
    {
        $itemType = ItemType::create(['designation' => 'Gas Turbine Meter']);
        $grandeur = Grandeur::create(['name' => 'Pressure Differential', 'symbol' => 'bar']);

        $itId = $itemType->id;
        $grId = $grandeur->id;

        $itemType->delete();
        $grandeur->delete();

        $this->assertSoftDeleted('item_types', ['id' => $itId]);
        $this->assertSoftDeleted('grandeurs', ['id' => $grId]);

        $this->assertNull(ItemType::find($itId));
        $this->assertNull(Grandeur::find($grId));

        $itemType->restore();
        $grandeur->restore();

        $this->assertNotNull(ItemType::find($itId));
        $this->assertNotNull(Grandeur::find($grId));
    }

    public function test_calibration_certificate_extraction_soft_delete_lifecycle(): void
    {
        $user = User::factory()->create();
        $extraction = CalibrationCertificateExtraction::create([
            'user_id' => $user->id,
            'file_path' => 'extractions/test.pdf',
            'file_hash' => hash('sha256', 'dummy_content'),
            'file_name' => 'test.pdf',
            'file_size' => 1024,
            'ai_model' => 'gemini-1.5-flash',
            'status' => 'pending',
        ]);

        $id = $extraction->id;

        // 1. Soft delete
        $extraction->delete();
        $this->assertSoftDeleted('calibration_certificate_extractions', ['id' => $id]);
        $this->assertNull(CalibrationCertificateExtraction::find($id));
        $this->assertNotNull(CalibrationCertificateExtraction::withTrashed()->find($id));

        // 2. Restore
        $extraction->restore();
        $this->assertNotNull(CalibrationCertificateExtraction::find($id));
        $this->assertDatabaseHas('calibration_certificate_extractions', [
            'id' => $id,
            'deleted_at' => null,
        ]);

        // 3. Force delete
        $extraction->forceDelete();
        $this->assertDatabaseMissing('calibration_certificate_extractions', ['id' => $id]);
    }
}
