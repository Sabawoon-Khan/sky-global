<?php

namespace Tests\Feature;

use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\User;
use App\Support\AfghanSolarDate;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceLedgerTest extends TestCase
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

    public function test_owner_can_view_monthly_ledger_page(): void
    {
        [$year, $month] = AfghanSolarDate::parts();
        $start = AfghanSolarDate::monthStart($year, $month)->toDateString();
        $end = AfghanSolarDate::monthEnd($year, $month)->toDateString();

        GeneralIncome::query()->create([
            'amount' => 1000,
            'currency' => 'AFN',
            'description' => 'Test income',
            'transaction_date' => $start,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'amount' => 250,
            'currency' => 'AFN',
            'description' => 'Test expense',
            'transaction_date' => $start,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('finance.ledger', [
                'period_start' => $start,
                'period_end' => $end,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Ledger/Index')
                ->has('ledger.sections', 3)
                ->where('ledger.totals.income_afn', 1000.0)
                ->where('ledger.totals.expense_afn', 250.0)
            );
    }

    public function test_owner_can_print_monthly_ledger(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.ledger.print'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Ledger/Print')
                ->has('ledger')
            );
    }
}
