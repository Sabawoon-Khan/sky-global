<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Procurement\Bid;
use App\Models\Procurement\ProcurementOpportunity;
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

    public function test_won_bid_creates_project(): void
    {
        $organization = Organization::query()->create([
            'organization_type_id' => OrganizationType::query()->create(['name' => 'Gov', 'slug' => 'gov'])->id,
            'name' => 'Ministry of Interior',
        ]);

        $opportunity = ProcurementOpportunity::query()->create([
            'organization_id' => $organization->id,
            'title' => 'Static Guard Services',
            'currency' => 'USD',
            'status' => 'open',
        ]);

        $bid = Bid::query()->create([
            'procurement_opportunity_id' => $opportunity->id,
            'bid_number' => 'B-2026-0001',
            'status' => 'submitted',
            'our_total_amount' => 50000,
            'currency' => 'USD',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->post(route('bidding.bids.won', $bid))
            ->assertRedirect();

        $bid->refresh();

        $this->assertSame('won', $bid->status);
        $this->assertNotNull($bid->project_id);
        $this->assertDatabaseHas('projects', [
            'id' => $bid->project_id,
            'organization_id' => $organization->id,
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $bid->project_id,
            'activity_type' => 'project_created',
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
