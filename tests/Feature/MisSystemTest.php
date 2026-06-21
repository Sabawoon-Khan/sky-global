<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MisSystemTest extends TestCase
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

    public function test_owner_can_view_organizations_index(): void
    {
        $this->actingAs($this->owner)
            ->get(route('organizations.index'))
            ->assertOk();
    }

    public function test_owner_can_create_organization(): void
    {
        $type = OrganizationType::query()->create([
            'name' => 'NGO',
            'slug' => 'ngo',
        ]);

        $this->actingAs($this->owner)
            ->post(route('organizations.store'), [
                'organization_type_id' => $type->id,
                'name' => 'Test NGO',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('organizations', ['name' => 'Test NGO']);
    }

    public function test_project_status_can_change_to_won(): void
    {
        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create(['name' => 'Gov', 'slug' => 'gov'])->id,
            'name' => 'Ministry of Interior',
        ]);

        $project = Project::query()->create([
            'organization_id' => $organization->id,
            'code' => 'GS-2026-TEST',
            'name' => 'Static Guard Services',
            'currency' => 'USD',
            'status' => 'submitted',
            'our_bid_amount' => 50000,
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->post(route('projects.status', $project), ['status' => 'won'])
            ->assertRedirect();

        $project->refresh();

        $this->assertSame('won', $project->status);
        $this->assertNotNull($project->won_at);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'activity_type' => 'status_change',
        ]);
    }

    public function test_viewer_cannot_create_organization(): void
    {
        $type = OrganizationType::query()->create([
            'name' => 'Private',
            'slug' => 'private',
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->post(route('organizations.store'), [
                'organization_type_id' => $type->id,
                'name' => 'Blocked Org',
            ])
            ->assertForbidden();
    }
}
