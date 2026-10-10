<?php

declare(strict_types=1);

namespace Tests\Feature\Financial;

use App\Enums\ExpenseAffiliation;
use App\Enums\ExpenseChargeType;
use App\Models\Expense;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
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
     * 1. Guest redirected to login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('financial.expenses'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 2. User without permission is forbidden.
     */
    public function test_user_without_permission_cannot_view_expenses(): void
    {
        $response = $this->actingAs($this->standardUser)->get(route('financial.expenses'));

        $response->assertForbidden();
    }

    /**
     * 3. Super Admin can view expenses index with seeded/created data and total amount.
     */
    public function test_super_admin_can_view_expenses_index(): void
    {
        Expense::create([
            'amount' => 50000.00,
            'date' => '2026-05-10',
            'description' => 'Office internet service',
            'type' => ExpenseAffiliation::Gmtm,
            'charge_type' => ExpenseChargeType::Fixed,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('financial.expenses', ['year' => '2026']));

        $response->assertOk();
        $response->assertSee('Office internet service');
        $response->assertSee('50 000.00');
    }

    /**
     * 4. Super Admin can access create expense page.
     */
    public function test_super_admin_can_view_create_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('financial.expenses.create'));

        $response->assertOk();
        $response->assertSee(__('Save Expense'));
    }

    /**
     * 5. Super Admin can create a new expense with valid data.
     */
    public function test_super_admin_can_store_gmtm_expense(): void
    {
        $payload = [
            'amount' => 12500.50,
            'date' => '2026-06-15',
            'type' => 'gmtm',
            'charge_type' => 'variable',
            'description' => 'Stationery and supplies',
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('financial.expenses.store'), $payload);

        $response->assertRedirect(route('financial.expenses'));
        $this->assertDatabaseHas('charges', [
            'amount' => 12500.50,
            'description' => 'Stationery and supplies',
            'type' => 'gmtm',
            'charge_type' => 'variable',
        ]);
    }

    /**
     * 6. Non-GMTM expense has charge_type cleared automatically by the service.
     */
    public function test_non_gmtm_expense_clears_charge_type(): void
    {
        $mission = Mission::create([
            'reference' => 'M-2026-EXP-TEST',
            'status' => 'planned',
        ]);

        $payload = [
            'amount' => 35000.00,
            'date' => '2026-07-01',
            'type' => 'mission',
            'charge_type' => 'fixed', // should be cleared by cleanData
            'mission_id' => $mission->id,
            'description' => 'Mission transport costs',
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('financial.expenses.store'), $payload);

        $response->assertRedirect(route('financial.expenses'));
        $this->assertDatabaseHas('charges', [
            'amount' => 35000.00,
            'type' => 'mission',
            'mission_id' => $mission->id,
            'charge_type' => null,
        ]);
    }

    /**
     * 7. Super Admin can view expense details page.
     */
    public function test_super_admin_can_view_expense_details(): void
    {
        $expense = Expense::create([
            'amount' => 8500.00,
            'date' => '2026-08-20',
            'description' => 'Server hosting fee',
            'type' => ExpenseAffiliation::Prisma,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('financial.expenses.show', $expense->id));

        $response->assertOk();
        $response->assertSee('Server hosting fee');
        $response->assertSee('8 500.00');
    }

    /**
     * 8. Super Admin can view edit page and update an expense.
     */
    public function test_super_admin_can_update_expense(): void
    {
        $expense = Expense::create([
            'amount' => 1000.00,
            'date' => '2026-09-01',
            'description' => 'Old description',
            'type' => ExpenseAffiliation::Gmtm,
            'charge_type' => ExpenseChargeType::Variable,
        ]);

        $editResponse = $this->actingAs($this->superAdmin)->get(route('financial.expenses.edit', $expense->id));
        $editResponse->assertOk();
        $editResponse->assertSee('Old description');

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('financial.expenses.update', $expense->id), [
            'amount' => 1500.00,
            'date' => '2026-09-02',
            'description' => 'Updated description',
            'type' => 'gmtm',
            'charge_type' => 'fixed',
        ]);

        $updateResponse->assertRedirect(route('financial.expenses'));
        $this->assertDatabaseHas('charges', [
            'id' => $expense->id,
            'amount' => 1500.00,
            'description' => 'Updated description',
            'charge_type' => 'fixed',
        ]);
    }

    /**
     * 9. Super Admin can delete an expense.
     */
    public function test_super_admin_can_delete_expense(): void
    {
        $expense = Expense::create([
            'amount' => 450.00,
            'date' => '2026-09-10',
            'description' => 'To be deleted',
            'type' => ExpenseAffiliation::Gmtm,
            'charge_type' => ExpenseChargeType::Variable,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('financial.expenses.destroy', $expense->id));

        $response->assertRedirect(route('financial.expenses'));
        $this->assertSoftDeleted('charges', [
            'id' => $expense->id,
        ]);
    }

    /**
     * 10. Expense store and update accept date formatted with month in the middle (DD/MM/YYYY).
     */
    public function test_expense_accepts_date_with_month_in_the_middle_slash_format(): void
    {
        $payload = [
            'amount' => 12500.00,
            'date' => '25/08/2026', // Day 25, Month 08 in the middle, Year 2026
            'type' => 'gmtm',
            'charge_type' => 'fixed',
            'description' => 'Office equipment maintenance',
        ];

        $storeResponse = $this->actingAs($this->superAdmin)->post(route('financial.expenses.store'), $payload);
        $storeResponse->assertRedirect(route('financial.expenses'));

        $created = Expense::where('description', 'Office equipment maintenance')->firstOrFail();
        $this->assertEquals('2026-08-25', $created->date->format('Y-m-d'));

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('financial.expenses.update', $created->id), [
            'amount' => 13000.00,
            'date' => '30/11/2026', // Day 30, Month 11 in the middle, Year 2026
            'type' => 'gmtm',
            'charge_type' => 'fixed',
            'description' => 'Office equipment maintenance updated',
        ]);

        $updateResponse->assertRedirect(route('financial.expenses'));

        $created->refresh();
        $this->assertEquals(13000.00, (float) $created->amount);
        $this->assertEquals('2026-11-30', $created->date->format('Y-m-d'));
        $this->assertEquals('Office equipment maintenance updated', $created->description);
    }
}
