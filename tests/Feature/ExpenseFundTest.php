<?php

namespace Tests\Feature;

use App\Models\Finance\ExpenseFund;
use App\Models\Finance\GeneralExpense;
use App\Models\Finance\ProjectExpense;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseFundTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
    }

    public function test_general_expenses_page_includes_cash_box_and_receipts(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 20000,
            'currency' => 'AFN',
            'received_from' => 'Finance manager',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('finance.general-expenses'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/GeneralExpenses/Index')
                ->has('expenseFunds', 1)
                ->where('expenseFunds.0.amount_received', 20000)
                ->where('cashBox.received', 20000)
                ->where('cashBox.spent', 0)
                ->where('cashBox.remaining', 20000)
                ->missing('expenseFundPickerOptions')
            );
    }

    public function test_two_receipts_share_one_cash_box_balance(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 10000,
            'currency' => 'AFN',
            'received_from' => 'Finance manager',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        ExpenseFund::query()->create([
            'amount_received' => 5000,
            'currency' => 'AFN',
            'received_from' => 'Director',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->post(route('finance.general-expenses.store'), [
                'description' => 'Office supplies',
                'amount' => 4000,
                'transaction_date' => now()->toDateString(),
                'paid_from_cash_box' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $cashBox = ExpenseFund::cashBox();

        $this->assertSame(15000.0, $cashBox['received']);
        $this->assertSame(4000.0, $cashBox['spent']);
        $this->assertSame(11000.0, $cashBox['remaining']);
        $this->assertDatabaseHas('general_expenses', [
            'description' => 'Office supplies',
            'paid_from_cash_box' => true,
            'expense_fund_id' => null,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->post(route('finance.general-expenses.store'), [
                'description' => 'Personal errand',
                'amount' => 1000,
                'transaction_date' => now()->toDateString(),
                'paid_from_cash_box' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(4000.0, ExpenseFund::cashBox()['spent']);
        $this->assertSame(11000.0, ExpenseFund::cashBox()['remaining']);
    }

    public function test_expense_amount_cannot_exceed_cash_box_remaining_balance(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 1000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->post(route('finance.general-expenses.store'), [
                'description' => 'Large purchase',
                'amount' => 1500,
                'transaction_date' => now()->toDateString(),
                'paid_from_cash_box' => 1,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0.0, ExpenseFund::cashBox()['spent']);
    }

    public function test_updating_expense_amount_recalculates_cash_box_balance(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 10000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $expense = GeneralExpense::query()->create([
            'paid_from_cash_box' => true,
            'amount' => 2000,
            'currency' => 'AFN',
            'transaction_date' => now()->toDateString(),
            'description' => 'Test',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->put(route('finance.general-expenses.update', $expense), [
                'amount' => 9000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $cashBox = ExpenseFund::cashBox();

        $this->assertSame(9000.0, $cashBox['spent']);
        $this->assertSame(1000.0, $cashBox['remaining']);
    }

    public function test_project_expense_from_cash_box_reduces_shared_balance(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 20000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $project = $this->makeProject();

        $this->actingAs($this->owner)
            ->from(route('projects.show', $project))
            ->post(route('projects.expenses.store', $project), [
                'amount' => 7000,
                'transaction_date' => now()->toDateString(),
                'description' => 'Site materials',
                'paid_from_cash_box' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $cashBox = ExpenseFund::cashBox();

        $this->assertSame(7000.0, $cashBox['spent']);
        $this->assertSame(13000.0, $cashBox['remaining']);
    }

    public function test_cannot_delete_receipt_that_would_uncover_cash_box_spending(): void
    {
        $fund = ExpenseFund::query()->create([
            'amount_received' => 5000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $project = $this->makeProject();

        ProjectExpense::query()->create([
            'project_id' => $project->id,
            'paid_from_cash_box' => true,
            'amount' => 100,
            'currency' => 'AFN',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'paid_from_cash_box' => true,
            'amount' => 100,
            'currency' => 'AFN',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->delete(route('finance.expense-funds.destroy', $fund))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('expense_funds', [
            'id' => $fund->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_delete_receipt_when_other_receipts_cover_spending(): void
    {
        ExpenseFund::query()->create([
            'amount_received' => 5000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $extra = ExpenseFund::query()->create([
            'amount_received' => 1000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'paid_from_cash_box' => true,
            'amount' => 100,
            'currency' => 'AFN',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->delete(route('finance.expense-funds.destroy', $extra))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('expense_funds', ['id' => $extra->id]);
        $this->assertSame(5000.0, ExpenseFund::cashBox()['received']);
        $this->assertSame(4900.0, ExpenseFund::cashBox()['remaining']);
    }

    public function test_lowering_fund_amount_below_spent_shows_warning(): void
    {
        $fund = ExpenseFund::query()->create([
            'amount_received' => 10000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'paid_from_cash_box' => true,
            'amount' => 8000,
            'currency' => 'AFN',
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->put(route('finance.expense-funds.update', $fund), [
                'amount_received' => 5000,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    private function makeProject(): Project
    {
        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create([
                'name' => 'Gov',
                'slug' => 'gov-fund-test',
            ])->id,
            'name' => 'Ministry of Interior',
        ]);

        return Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-FUND',
            'name' => 'Static Guard Services',
            'currency' => 'AFN',
            'status' => 'won',
            'created_by' => $this->owner->id,
        ]);
    }
}
