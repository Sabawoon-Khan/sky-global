<?php

namespace Tests\Feature;

use App\Models\Finance\FinanceCategory;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanceCategoryTest extends TestCase
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

    public function test_general_income_page_includes_categories(): void
    {
        $this->actingAs($this->owner)
            ->get(route('finance.general-income'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/GeneralIncome/Index')
                ->has('categories')
                ->where('categories.0.name', 'Grant')
            );
    }

    public function test_project_show_includes_finance_categories(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->owner)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/projects/Show')
                ->has('financeCategories')
            );
    }

    public function test_owner_can_add_and_remove_a_category(): void
    {
        $this->actingAs($this->owner)
            ->from(route('finance.general-income'))
            ->post(route('finance.categories.store'), [
                'name' => 'Donor gift',
                'applies_to' => 'income',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_categories', [
            'name' => 'Donor gift',
            'applies_to' => 'income',
        ]);

        $category = FinanceCategory::query()->where('name', 'Donor gift')->firstOrFail();

        $this->actingAs($this->owner)
            ->from(route('finance.general-income'))
            ->delete(route('finance.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('finance_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_owner_can_record_general_income_with_a_category(): void
    {
        $this->actingAs($this->owner)
            ->from(route('finance.general-income'))
            ->post(route('finance.general-incomes.store'), [
                'description' => 'USAID grant',
                'category' => 'Grant',
                'amount' => 1500,
                'transaction_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('general_incomes', [
            'description' => 'USAID grant',
            'category' => 'Grant',
            'amount' => 1500,
        ]);
    }

    public function test_owner_can_record_project_income_and_expense_with_categories(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->owner)
            ->from(route('projects.show', $project))
            ->post(route('projects.incomes.store', $project), [
                'amount' => 2000,
                'transaction_date' => now()->toDateString(),
                'category' => 'Client payment',
                'description' => 'Milestone 1',
            ])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->from(route('projects.show', $project))
            ->post(route('projects.expenses.store', $project), [
                'amount' => 400,
                'transaction_date' => now()->toDateString(),
                'category' => 'Transport',
                'description' => 'Fuel',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_incomes', [
            'project_id' => $project->id,
            'category' => 'Client payment',
            'amount' => 2000,
        ]);

        $this->assertDatabaseHas('project_expenses', [
            'project_id' => $project->id,
            'category' => 'Transport',
            'amount' => 400,
        ]);
    }

    public function test_viewer_cannot_create_a_category(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->post(route('finance.categories.store'), [
                'name' => 'Blocked',
                'applies_to' => 'income',
            ])
            ->assertForbidden();
    }

    private function makeProject(): Project
    {
        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create([
                'name' => 'Gov',
                'slug' => 'gov',
            ])->id,
            'name' => 'Ministry of Interior',
        ]);

        return Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-CAT',
            'name' => 'Static Guard Services',
            'currency' => 'AFN',
            'status' => 'won',
            'created_by' => $this->owner->id,
        ]);
    }
}
