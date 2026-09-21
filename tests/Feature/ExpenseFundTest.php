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

    public function test_general_expenses_page_includes_expense_funds(): void
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
                ->where('expenseFunds.0.remaining_amount', 20000)
            );
    }

    public function test_expense_linked_to_fund_updates_remaining(): void
    {
        $fund = ExpenseFund::query()->create([
            'amount_received' => 20000,
            'currency' => 'AFN',
            'received_from' => 'Finance manager',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('finance.general-expenses'))
            ->post(route('finance.general-expenses.store'), [
                'description' => 'Office supplies',
                'amount' => 5000,
                'transaction_date' => now()->toDateString(),
                'expense_fund_id' => $fund->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fund->refresh();

        $this->assertSame(5000.0, $fund->spentAmount());
        $this->assertSame(15000.0, $fund->remainingAmount());

        $this->actingAs($this->owner)
            ->get(route('finance.general-expenses'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('expenseFunds.0.spent_amount', 5000)
                ->where('expenseFunds.0.remaining_amount', 15000)
            );
    }

    public function test_overdrawn_fund_returns_warning_flash(): void
    {
        $fund = ExpenseFund::query()->create([
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
                'expense_fund_id' => $fund->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $this->assertTrue($fund->fresh()->isOverdrawn());
    }

    public function test_updating_expense_amount_recalculates_fund_balance(): void
    {
        $fund = ExpenseFund::query()->create([
            'amount_received' => 10000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        $expense = GeneralExpense::query()->create([
            'expense_fund_id' => $fund->id,
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

        $this->assertSame(9000.0, $fund->fresh()->spentAmount());
        $this->assertSame(1000.0, $fund->fresh()->remainingAmount());
    }

    public function test_project_expense_linked_to_fund_updates_remaining(): void
    {
        $fund = ExpenseFund::query()->create([
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
                'expense_fund_id' => $fund->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fund->refresh();

        $this->assertSame(7000.0, $fund->spentAmount());
        $this->assertSame(13000.0, $fund->remainingAmount());
    }

    public function test_cannot_delete_fund_with_linked_project_expense(): void
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
            'expense_fund_id' => $fund->id,
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

        $this->assertDatabaseHas('expense_funds', ['id' => $fund->id]);
    }

    public function test_cannot_delete_fund_with_linked_expenses(): void
    {
        $fund = ExpenseFund::query()->create([
            'amount_received' => 5000,
            'currency' => 'AFN',
            'received_date' => now()->toDateString(),
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'expense_fund_id' => $fund->id,
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

        $this->assertDatabaseHas('expense_funds', ['id' => $fund->id]);
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
            'expense_fund_id' => $fund->id,
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
            ->assertSessionHas('success')
            ->assertSessionHas('warning');
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
