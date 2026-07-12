<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MisPermissionRegistrar;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MisPermissionRegistrarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_standard_permissions_for_each_module(): void
    {
        app(MisPermissionRegistrar::class)->sync();

        $this->assertDatabaseHas('permissions', ['name' => 'projects.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'projects.create']);
        $this->assertDatabaseHas('permissions', ['name' => 'projects.edit']);
        $this->assertDatabaseHas('permissions', ['name' => 'projects.delete']);
        $this->assertDatabaseHas('permissions', ['name' => 'projects.archive']);
        $this->assertDatabaseHas('permissions', ['name' => 'bidding.view_competitors']);
        $this->assertDatabaseHas('permissions', ['name' => 'settings.manage_users']);
    }

    public function test_sync_is_idempotent(): void
    {
        $registrar = app(MisPermissionRegistrar::class);

        $registrar->sync();
        $countAfterFirstSync = Permission::query()->count();

        $registrar->sync();

        $this->assertSame($countAfterFirstSync, Permission::query()->count());
    }

    public function test_new_module_in_config_appears_on_roles_page(): void
    {
        config()->set('mis_permissions.modules.inventory', [
            'extra' => [],
        ]);

        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $response = $this->actingAs($owner)
            ->get(route('settings.roles.index'));

        $response->assertOk();

        $permissionNames = collect($response->inertiaProps('permissions'))
            ->pluck('name')
            ->all();

        $this->assertContains('inventory.view', $permissionNames);
        $this->assertContains('inventory.create', $permissionNames);
        $this->assertContains('inventory.edit', $permissionNames);
        $this->assertContains('inventory.delete', $permissionNames);
        $this->assertContains('inventory.archive', $permissionNames);
    }
}
