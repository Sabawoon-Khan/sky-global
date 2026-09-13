<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\Project\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MisNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $hrViewer;

    private User $financeOnly;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create(['name' => 'Owner User']);
        $this->owner->assignRole('Owner');

        $this->hrViewer = User::factory()->create(['name' => 'HR Viewer']);
        $this->hrViewer->givePermissionTo('hr.view');

        $this->financeOnly = User::factory()->create(['name' => 'Finance Only']);
        $this->financeOnly->givePermissionTo('finance.view');
    }

    public function test_creating_an_employee_notifies_hr_viewers_but_not_the_actor(): void
    {
        $this->actingAs($this->owner)
            ->post(route('hr.employees.store'), [
                'first_name' => 'Ahmad',
                'last_name' => 'Khan',
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertSame(0, $this->owner->notifications()->count());
        $this->assertSame(1, $this->hrViewer->notifications()->count());
        $this->assertSame(0, $this->financeOnly->notifications()->count());

        $notification = $this->hrViewer->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Ahmad Khan created', $notification->data['title']);
        $this->assertSame('success', $notification->data['type']);
        $this->assertSame('hr', $notification->data['module']);
        $this->assertStringContainsString('/hr/employees/', (string) $notification->data['action_url']);
    }

    public function test_blocking_an_employee_sends_a_warning_status_notification(): void
    {
        Storage::fake('local');

        $employee = Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $this->actingAs($this->owner)
            ->from(route('hr.employees.show', $employee))
            ->put(route('hr.employees.update', $employee), [
                'status' => 'blocked',
                'reason' => 'Policy violation',
                'attachment' => UploadedFile::fake()->create('notice.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $notification = $this->hrViewer->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Ahmad Khan status changed', $notification->data['title']);
        $this->assertSame('warning', $notification->data['type']);
    }

    public function test_creating_an_invoice_notifies_finance_viewers(): void
    {
        $this->actingAs($this->owner)
            ->post(route('finance.invoices.store'), [
                'issue_date' => '2026-09-01',
                'currency' => 'AFN',
                'status' => 'draft',
                'subtotal' => 100,
                'tax' => 0,
                'total' => 100,
            ])
            ->assertRedirect();

        $this->assertSame(1, $this->financeOnly->notifications()->count());
        $this->assertSame(0, $this->hrViewer->notifications()->count());
        $this->assertSame(0, $this->owner->notifications()->count());

        $notification = $this->financeOnly->notifications()->first();
        $this->assertSame('finance', $notification?->data['module']);
        $this->assertSame('success', $notification?->data['type']);
    }

    public function test_project_status_change_notifies_project_viewers(): void
    {
        $viewer = User::factory()->create(['name' => 'Project Viewer']);
        $viewer->givePermissionTo('projects.view');

        $project = Project::query()->create([
            'organization_id' => Organization::query()->create([
                'organization_type_id' => OrganizationType::query()->create([
                    'name' => 'Gov',
                    'slug' => 'gov',
                ])->id,
                'name' => 'Test Client',
            ])->id,
            'code' => 'GS-2026-NOTE',
            'name' => 'Kabul Compound',
            'currency' => 'AFN',
            'status' => 'draft',
            'created_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->from(route('projects.show', $project))
            ->post(route('projects.status', $project), [
                'status' => 'submitted',
            ])
            ->assertRedirect();

        $notification = $viewer->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('Kabul Compound status changed', $notification->data['title']);
        $this->assertSame('projects', $notification->data['module']);
        $this->assertSame(0, $this->financeOnly->notifications()->count());
    }

    public function test_website_contact_notifies_settings_viewers(): void
    {
        Mail::fake();

        $this->from(route('website.contact'))
            ->post(route('website.contact.store'), [
                'name' => 'Ahmad Khan',
                'email' => 'ahmad@example.com',
                'message' => 'We need static guarding.',
                'website' => '',
            ])
            ->assertRedirect();

        $this->assertSame(1, $this->owner->notifications()->count());
        $this->assertSame(0, $this->hrViewer->notifications()->count());

        $notification = $this->owner->notifications()->first();
        $this->assertSame('New website message', $notification?->data['title']);
        $this->assertSame('warning', $notification?->data['type']);
        $this->assertSame('settings', $notification?->data['module']);
    }

    public function test_notification_bell_lists_and_marks_items_read(): void
    {
        $this->actingAs($this->owner)
            ->post(route('hr.employees.store'), [
                'first_name' => 'Ahmad',
                'last_name' => 'Khan',
            ]);

        $this->actingAs($this->hrViewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread_count', 1)
            );

        $id = $this->hrViewer->notifications()->first()?->id;
        $this->assertNotNull($id);

        $this->actingAs($this->hrViewer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Ahmad Khan created');

        $this->actingAs($this->hrViewer)
            ->post(route('notifications.read', $id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($this->hrViewer->fresh()->notifications()->first()?->read_at);
    }
}
