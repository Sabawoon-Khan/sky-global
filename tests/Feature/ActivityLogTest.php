<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('settings.view_login_logs');
        Permission::findOrCreate('settings.manage_users');
    }

    public function test_employee_create_and_update_are_logged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $employee = Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $created = Activity::query()
            ->where('log_name', 'crud')
            ->where('event', 'created')
            ->where('subject_type', 'employee')
            ->where('subject_id', $employee->id)
            ->first();

        $this->assertNotNull($created);
        $this->assertSame($user->id, $created->causer_id);

        $employee->update(['first_name' => 'Mahmood']);

        $updated = Activity::query()
            ->where('log_name', 'crud')
            ->where('event', 'updated')
            ->where('subject_type', 'employee')
            ->where('subject_id', $employee->id)
            ->first();

        $this->assertNotNull($updated);
        $this->assertSame($user->id, $updated->causer_id);

        $changes = $updated->attribute_changes?->toArray() ?? [];
        $this->assertSame('Ahmad', $changes['old']['first_name'] ?? null);
        $this->assertSame('Mahmood', $changes['attributes']['first_name'] ?? null);
    }

    public function test_user_password_is_not_logged(): void
    {
        $user = User::factory()->create();

        $created = Activity::query()
            ->where('log_name', 'crud')
            ->where('event', 'created')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->first();

        $this->assertNotNull($created);

        $changes = $created->attribute_changes?->toArray() ?? [];
        $this->assertArrayNotHasKey('password', $changes['attributes'] ?? []);
        $this->assertArrayNotHasKey('remember_token', $changes['attributes'] ?? []);
        $this->assertArrayNotHasKey('two_factor_secret', $changes['attributes'] ?? []);
    }

    public function test_admin_can_view_activity_logs_page(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('settings.view_login_logs');

        $this->actingAs($admin);

        Employee::query()->create([
            'first_name' => 'Ahmad',
            'last_name' => 'Khan',
            'status' => 'active',
            'is_permanent' => true,
        ]);

        $response = $this->get(route('settings.activity-logs.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/ActivityLogs/Index')
            ->has('logs.data')
            ->where('logs.data.0.event', 'created')
            ->where('logs.data.0.subject_type', 'Employee')
            ->where('logs.data.0.subject_label', 'Ahmad Khan')
            ->where('logs.data.0.causer.id', $admin->id));
    }

    public function test_user_with_manage_users_permission_can_view_activity_logs_page(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo('settings.manage_users');

        $this->actingAs($admin)
            ->get(route('settings.activity-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/ActivityLogs/Index'));
    }

    public function test_user_without_permission_cannot_view_activity_logs_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.activity-logs.index'))
            ->assertForbidden();
    }
}
