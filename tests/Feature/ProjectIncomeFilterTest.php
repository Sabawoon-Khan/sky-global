<?php

namespace Tests\Feature;

use App\Models\Finance\ProjectIncome;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectIncomeFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Project $project;

    private Project $otherProject;

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
            'code' => 'GS-2026-INC',
            'name' => 'Embassy Guard Force',
            'currency' => 'AFN',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);

        $this->otherProject = Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-OTH',
            'name' => 'Other Site',
            'currency' => 'AFN',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_owner_can_filter_project_income_by_date_status_and_project(): void
    {
        $match = ProjectIncome::query()->create([
            'project_id' => $this->project->id,
            'amount' => 1000,
            'currency' => 'AFN',
            'description' => 'March payment',
            'category' => 'Client payment',
            'transaction_date' => '2026-03-15',
            'status' => 'approved',
            'created_by' => $this->owner->id,
        ]);

        ProjectIncome::query()->create([
            'project_id' => $this->otherProject->id,
            'amount' => 500,
            'currency' => 'AFN',
            'description' => 'April payment',
            'category' => 'Other',
            'transaction_date' => '2026-04-10',
            'status' => 'pending',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('finance.income', [
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-31',
                'status' => 'approved',
                'project_id' => $this->project->id,
                'search' => 'March',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/finance/Income/Index')
                ->where('filters.date_from', '2026-03-01')
                ->where('filters.date_to', '2026-03-31')
                ->where('filters.status', 'approved')
                ->where('filters.project_id', $this->project->id)
                ->where('stats.count', 1)
                ->where('stats.total', 1000)
                ->has('incomes.data', 1)
                ->where('incomes.data.0.id', $match->id)
            );
    }
}
