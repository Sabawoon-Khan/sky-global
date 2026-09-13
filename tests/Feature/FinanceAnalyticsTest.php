<?php

namespace Tests\Feature;

use App\Models\Finance\GeneralExpense;
use App\Models\Finance\GeneralIncome;
use App\Models\Finance\Invoice;
use App\Models\Finance\ProjectExpense;
use App\Models\Finance\ProjectIncome;
use App\Models\Finance\TaxPayment;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create([
                'name' => 'Gov',
                'slug' => 'gov',
            ])->id,
            'name' => 'Ministry of Interior',
        ]);

        $this->project = Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-FIN',
            'name' => 'Embassy Guard Force',
            'currency' => 'AFN',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_guest_is_redirected_from_finance_analytics(): void
    {
        $this->get(route('analytics.finance'))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_the_finance_report(): void
    {
        $this->seedCurrentYearBooks();

        $this->actingAs($this->owner)
            ->get(route('analytics.finance'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/Finance')
                ->where('year', now()->year)
                ->where('stats.total_income', 8000)
                ->where('stats.total_expense', 2500)
                ->where('stats.operating_net', 5500)
                ->where('stats.billed', 3000)
                ->where('stats.outstanding', 3000)
                ->where('tax.paid', 400)
                ->has('statement')
                ->has('charts.monthly')
                ->has('projectProfitability', 1)
                ->where('projectProfitability.0.code', 'GS-2026-FIN')
                ->where('projectProfitability.0.income', 5000)
                ->where('projectProfitability.0.expense', 1500)
            );
    }

    public function test_year_filter_excludes_other_years(): void
    {
        $this->seedCurrentYearBooks();

        ProjectIncome::query()->create([
            'project_id' => $this->project->id,
            'amount' => 9000,
            'currency' => 'AFN',
            'category' => 'Client payment',
            'transaction_date' => now()->subYear()->startOfYear()->toDateString(),
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('analytics.finance', ['year' => now()->year]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.project_income', 5000)
                ->where('stats.total_income', 8000)
            );

        $this->actingAs($this->owner)
            ->get(route('analytics.finance', ['year' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('year', null)
                ->where('stats.project_income', 14000)
                ->where('stats.total_income', 17000)
            );
    }

    public function test_owner_can_print_the_finance_report(): void
    {
        $this->seedCurrentYearBooks();

        $this->actingAs($this->owner)
            ->get(route('analytics.finance.print', ['year' => now()->year]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/analytics/FinancePrint')
                ->where('year', now()->year)
                ->has('statement')
                ->has('company.name')
                ->has('projectProfitability')
            );
    }

    private function seedCurrentYearBooks(): void
    {
        $date = now()->startOfYear()->addMonths(2)->toDateString();

        ProjectIncome::query()->create([
            'project_id' => $this->project->id,
            'amount' => 5000,
            'currency' => 'AFN',
            'category' => 'Client payment',
            'transaction_date' => $date,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        GeneralIncome::query()->create([
            'amount' => 3000,
            'currency' => 'AFN',
            'category' => 'Grant',
            'transaction_date' => $date,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        ProjectExpense::query()->create([
            'project_id' => $this->project->id,
            'amount' => 1500,
            'currency' => 'AFN',
            'category' => 'Transport',
            'transaction_date' => $date,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        GeneralExpense::query()->create([
            'amount' => 1000,
            'currency' => 'AFN',
            'category' => 'Salary',
            'transaction_date' => $date,
            'status' => 'recorded',
            'created_by' => $this->owner->id,
        ]);

        Invoice::query()->create([
            'project_id' => $this->project->id,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => $date,
            'subtotal' => 3000,
            'tax' => 0,
            'total' => 3000,
            'currency' => 'AFN',
            'status' => 'sent',
            'created_by' => $this->owner->id,
        ]);

        TaxPayment::query()->create([
            'period_type' => 'yearly',
            'year' => now()->year,
            'period_start' => now()->startOfYear()->toDateString(),
            'period_end' => now()->endOfYear()->toDateString(),
            'rate' => 0.1,
            'rate_percent' => 10,
            'tax_due' => 550,
            'amount' => 400,
            'company_amount' => 400,
            'currency' => 'AFN',
            'payment_date' => $date,
            'status' => 'paid',
            'created_by' => $this->owner->id,
        ]);
    }
}
