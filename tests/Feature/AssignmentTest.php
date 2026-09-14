<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $assignee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin User', 'is_active' => true]);
        $this->admin->assignRole('Owner');

        $this->assignee = User::factory()->create(['name' => 'Assignee User', 'is_active' => true]);
        $this->assignee->givePermissionTo('assignments.view');
    }

    public function test_admin_can_create_assignment_and_notifies_assignees(): void
    {
        $this->actingAs($this->admin)
            ->post(route('assignments.store'), [
                'description' => 'Complete the safety checklist.',
                'assigned_on' => now()->toDateString(),
                'reply_by' => now()->addDays(3)->toDateString(),
                'assign_to' => 'users',
                'user_ids' => [$this->assignee->id],
            ])
            ->assertRedirect();

        $assignment = Assignment::query()->first();
        $this->assertNotNull($assignment);
        $this->assertSame('Complete the safety checklist.', $assignment->description);
        $this->assertSame(1, $assignment->recipients()->count());

        $this->assertSame(0, $this->admin->notifications()->count());
        $this->assertSame(1, $this->assignee->notifications()->count());

        $notification = $this->assignee->notifications()->first();
        $this->assertSame('New assignment', $notification->data['title']);
        $this->assertStringContainsString('/assignments/', (string) $notification->data['action_url']);
    }

    public function test_assignee_can_reply_and_creator_gets_notified(): void
    {
        $assignment = Assignment::query()->create([
            'description' => 'Upload weekly report.',
            'reply_by' => now()->addWeek(),
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $assignment->recipients()->create([
            'user_id' => $this->assignee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->assignee)
            ->post(route('assignments.replies.store', $assignment), [
                'description' => 'Report uploaded.',
            ])
            ->assertRedirect();

        $this->assertSame('submitted', $assignment->recipients()->first()->status);
        $this->assertSame(1, $assignment->replies()->count());
        $this->assertSame(1, $this->admin->notifications()->count());
    }

    public function test_view_only_assignee_sees_assigned_record_on_index(): void
    {
        $assignment = Assignment::query()->create([
            'description' => 'Field report.',
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $assignment->recipients()->create([
            'user_id' => $this->assignee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->assignee)
            ->get(route('assignments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('mis/assignments/Index')
                ->has('assignments.data', 1)
                ->where('assignments.data.0.description', 'Field report.'));

        $this->actingAs($this->assignee)
            ->get(route('assignments.show', $assignment))
            ->assertOk();
    }

    public function test_non_recipient_cannot_view_assignment(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        $other->givePermissionTo('assignments.view');

        $assignment = Assignment::query()->create([
            'description' => 'Private task.',
            'reply_by' => now()->addDay(),
            'status' => 'pending',
            'created_by' => $this->admin->id,
        ]);

        $assignment->recipients()->create([
            'user_id' => $this->assignee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($other)
            ->get(route('assignments.show', $assignment))
            ->assertForbidden();
    }
}
