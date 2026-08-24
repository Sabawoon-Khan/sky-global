<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public function test_owner_can_update_user_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
        ]);

        $this->actingAs($this->owner)
            ->put(route('settings.users.update', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));
    }

    public function test_updating_roles_without_password_leaves_password_unchanged(): void
    {
        $user = User::factory()->create([
            'password' => 'KeepPassword123!',
        ]);
        $originalHash = $user->password;

        $this->actingAs($this->owner)
            ->put(route('settings.users.update', $user), [
                'roles' => ['Staff'],
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame($originalHash, $user->password);
        $this->assertTrue($user->hasRole('Staff'));
    }

    public function test_owner_can_delete_user_without_system_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $user))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_owner_cannot_delete_own_account(): void
    {
        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $this->owner))
            ->assertRedirect()
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $this->owner->id]);
    }

    public function test_owner_cannot_delete_user_with_system_activity(): void
    {
        $user = User::factory()->create();

        DB::table('storage_backups')->insert([
            'filename' => 'backup.zip',
            'path' => 'backups/backup.zip',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->delete(route('settings.users.destroy', $user))
            ->assertRedirect()
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_users_index_marks_deletable_users(): void
    {
        $deletable = User::factory()->create(['name' => 'Deletable User']);
        $blocked = User::factory()->create(['name' => 'Blocked User']);

        DB::table('storage_backups')->insert([
            'filename' => 'backup.zip',
            'path' => 'backups/backup.zip',
            'created_by' => $blocked->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(route('settings.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('users.data')
                ->where('users.data', function ($users) use ($deletable, $blocked) {
                    $byId = collect($users)->keyBy('id');

                    return ($byId[$deletable->id]['can_delete'] ?? false) === true
                        && ($byId[$blocked->id]['can_delete'] ?? true) === false
                        && ($byId[$this->owner->id]['can_delete'] ?? true) === false;
                }));
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
