<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Enums\EmployeePosition;
use App\Enums\EmployeeStatus;
use App\Enums\ExpenseAffiliation;
use App\Enums\ExpenseChargeType;
use App\Enums\MissionStatus;
use App\Models\Attachment;
use App\Models\AttachmentItem;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ItemType;
use App\Models\Mission;
use App\Models\MissionOrder;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MissionStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Customer $customer;

    protected Site $site;

    protected Contract $contract;

    protected ContractItem $contractItem;

    protected Employee $leader;

    protected Employee $specialist;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        $this->artisan('permissions:sync-tables');

        $superRole = Role::firstOrCreate(['name' => 'Super-Admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->superAdmin->assignRole($superRole);

        $this->customer = Customer::create([
            'company_name' => 'Sonatrach DP HMD',
            'short_name' => 'SH-DP',
        ]);

        $this->site = Site::create([
            'customer_id' => $this->customer->id,
            'full_name' => 'Hassi Messaoud Production Site',
            'short_name' => 'HMD',
        ]);

        $this->contract = Contract::create([
            'reference' => 'CTR-2026-STAT',
            'object' => 'Measurement contract',
            'customer_id' => $this->customer->id,
            'date_signature' => '2026-01-01',
            'duree' => 12,
            'montant_global_prevu' => 10000000,
        ]);

        $itemType = ItemType::create(['designation' => 'Flow Calibration']);
        $this->contractItem = ContractItem::create([
            'contract_id' => $this->contract->id,
            'item_type_id' => $itemType->id,
            'designation' => 'Flow meter verification',
            'quantity' => 100,
            'unit_price' => 25000.00,
            'type' => 'service',
        ]);

        $this->leader = Employee::create([
            'full_name' => 'Ahmed Amine',
            'registration_number' => 'EMP-001',
            'position' => EmployeePosition::MeteringTechnician,
            'status' => EmployeeStatus::Active,
            'join_date' => '2024-01-01',
            'salary' => 120000,
            'daily_rate' => 5000.00,
        ]);

        $this->specialist = Employee::create([
            'full_name' => 'Karim Benali',
            'registration_number' => 'EMP-002',
            'position' => EmployeePosition::MeteringTechnician,
            'status' => EmployeeStatus::Active,
            'join_date' => '2024-01-01',
            'salary' => 90000,
            'daily_rate' => 3000.00,
        ]);
    }

    public function test_can_render_mission_statistics_with_full_unit_economics(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'reference' => 'M-2026-STAT-01',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-10', // 10 days
            'mob_dmob_days' => 2, // 8 operational days
            'status' => MissionStatus::Active->value,
        ]);

        // Attachment linked to mission with 4 units @ 25,000 = 100,000 DZD revenue
        $attachment = Attachment::create([
            'code_ref' => 'ATT-2026-STAT-01',
            'mission_id' => $mission->id,
            'contract_id' => $this->contract->id,
            'site_id' => $this->site->id,
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-10',
            'status' => 'draft',
        ]);

        AttachmentItem::create([
            'attachment_id' => $attachment->id,
            'contract_item_id' => $this->contractItem->id,
            'actual_quantity' => 4.0,
            'planned_quantity' => 4.0,
        ]);

        // Mission Orders:
        // Leader: 10 days * 5,000 = 50,000
        // Specialist: 10 days * 3,000 = 30,000
        // Total HR = 80,000
        MissionOrder::create([
            'mission_id' => $mission->id,
            'employee_id' => $this->leader->id,
            'is_leader' => true,
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'daily_rate' => 5000.00,
            'status' => 'active',
        ]);

        MissionOrder::create([
            'mission_id' => $mission->id,
            'employee_id' => $this->specialist->id,
            'is_leader' => false,
            'started_at' => '2026-10-01',
            'ended_at' => '2026-10-10',
            'daily_rate' => 3000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.statistics', $mission->id));

        $response->assertOk();
        $response->assertViewIs('operations.missions.statistics');

        // Revenue: 100,000.00
        $response->assertSee('100,000.00');

        // Total HR Costs: 80,000.00
        $response->assertSee('80,000.00');

        // Gross Profit: 20,000.00
        $response->assertSee('20,000.00');

        // Assigned Staff: 2 Specialists
        $response->assertSee('Specialists');

        // Gross Margin: 20.00%
        $response->assertSee('20.00%');

        // Unit Economics: Gross Daily Yield = 20,000 / 8 op days = 2,500.00 DA/j
        $response->assertSee('2,500.00');

        // Net Daily Yield Reel = 20,000 / 10 days = 2,000.00 DA/j
        $response->assertSee('2,000.00');

        // Total Field Expenses = 80,000.00 DZD
        $response->assertSee('80,000.00');

        // General Turnover per day = 100,000 / 10 days = 10,000.00 DA/j
        $response->assertSee('10,000.00');

        // Both employees are in the breakdown
        $response->assertSee('Ahmed Amine');
        $response->assertSee('Karim Benali');
        $response->assertSee('50,000.00');
        $response->assertSee('30,000.00');

        // Attachment reference is visible
        $response->assertSee('ATT-2026-STAT-01');
    }

    public function test_can_filter_mission_statistics_by_year(): void
    {
        /** @var Mission $mission */
        $mission = Mission::create([
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'reference' => 'M-2026-STAT-02',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'mob_dmob_days' => 1,
            'status' => MissionStatus::Planned->value,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.statistics', [
            'mission' => $mission->id,
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            return $stats['current_year'] === 2025;
        });
    }

    public function test_mission_statistics_automatically_calculates_annual_overhead_based_on_mission_year(): void
    {
        // Mission in 2026 (5 days)
        /** @var Mission $mission2026 */
        $mission2026 = Mission::create([
            'site_id' => $this->site->id,
            'contract_id' => $this->contract->id,
            'reference' => 'M-2026-OVERHEAD',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-05', // 5 days
            'mob_dmob_days' => 0,
            'status' => MissionStatus::Active->value,
        ]);

        // Attachment revenue: 100,000 DZD
        $attachment = Attachment::create([
            'code_ref' => 'ATT-2026-OH',
            'mission_id' => $mission2026->id,
            'contract_id' => $this->contract->id,
            'site_id' => $this->site->id,
            'status' => 'approved',
        ]);
        AttachmentItem::create([
            'attachment_id' => $attachment->id,
            'contract_item_id' => $this->contractItem->id,
            'actual_quantity' => 4.0, // 4 * 25,000 = 100,000
            'planned_quantity' => 4.0,
        ]);

        // Annual GMTM fixed and variable charges for 2026
        Expense::create([
            'amount' => 50000.00,
            'date' => '2026-02-15',
            'type' => ExpenseAffiliation::Gmtm,
            'charge_type' => ExpenseChargeType::Fixed,
            'description' => 'Office Rent 2026',
        ]);

        Expense::create([
            'amount' => 50000.00,
            'date' => '2026-04-10',
            'type' => ExpenseAffiliation::Gmtm,
            'charge_type' => ExpenseChargeType::Variable,
            'description' => 'Office Utilities 2026',
        ]);

        // Request without manual year filter: must automatically derive 2026 from mission date
        $response = $this->actingAs($this->superAdmin)->get(route('operations.missions.statistics', $mission2026->id));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats) {
            // Fiscal year automatically matches mission start date (2026)
            $this->assertEquals(2026, $stats['current_year']);

            // Total annual GMTM charges for 2026: 50,000 + 50,000 = 100,000
            $this->assertEquals(100000.00, $stats['company_annual_stats']['details']['gmtm']['total_expenses']);

            // 5 company working days, daily rate = 100,000 / 5 = 20,000 DA/day
            $this->assertEquals(20000.00, $stats['gmtm_expense_rate']);

            // Gross profit = 100,000 (no direct expenses)
            $this->assertEquals(100000.00, $stats['profit']['gross']);

            // Net profit = 100,000 - (5 days * 20,000) = 0.00
            $this->assertEquals(0.00, (float) $stats['profit']['net']);

            return true;
        });

        $response->assertSee('100,000.00');
        $response->assertSee('20,000.00');
    }
}
