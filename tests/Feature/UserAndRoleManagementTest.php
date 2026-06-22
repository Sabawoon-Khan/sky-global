<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAndRoleManagementTest extends TestCase
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

    public function test_owner_can_view_users_index(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.users.index'))
            ->assertOk();
    }

    public function test_owner_can_create_user_with_roles(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.users.store'), [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'roles' => ['Staff'],
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'new@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Staff'));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_viewer_cannot_create_user(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->post(route('settings.users.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_view_roles_index(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.roles.index'))
            ->assertOk();
    }

    public function test_owner_can_create_role_with_permissions(): void
    {
        $this->actingAs($this->owner)
            ->post(route('settings.roles.store'), [
                'name' => 'Auditor',
                'permissions' => ['finance.view', 'projects.view'],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'Auditor')->first();

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('finance.view'));
        $this->assertTrue($role->hasPermissionTo('projects.view'));
    }

    public function test_owner_can_update_role_permissions(): void
    {
        $role = Role::findOrCreate('Auditor');

        $this->actingAs($this->owner)
            ->put(route('settings.roles.update', $role), [
                'name' => 'Auditor',
                'permissions' => ['hr.view'],
            ])
            ->assertRedirect();

        $role->refresh();

        $this->assertTrue($role->hasPermissionTo('hr.view'));
        $this->assertFalse($role->hasPermissionTo('finance.view'));
    }

    public function test_owner_cannot_delete_protected_role(): void
    {
        $ownerRole = Role::findByName('Owner');

        $this->actingAs($this->owner)
            ->delete(route('settings.roles.destroy', $ownerRole))
            ->assertRedirect()
            ->assertSessionHasErrors('role');
    }

    public function test_owner_cannot_delete_role_assigned_to_users(): void
    {
        $staffRole = Role::findByName('Staff');
        $user = User::factory()->create();
        $user->assignRole('Staff');

        $this->actingAs($this->owner)
            ->delete(route('settings.roles.destroy', $staffRole))
            ->assertRedirect()
            ->assertSessionHasErrors('role');
    }

    public function test_owner_can_delete_unused_custom_role(): void
    {
        $role = Role::findOrCreate('Temporary');

        $this->actingAs($this->owner)
            ->delete(route('settings.roles.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', ['name' => 'Temporary']);
    }
}
