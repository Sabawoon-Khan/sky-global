<?php

namespace Database\Seeders;

use App\Services\MisPermissionRegistrar;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(MisPermissionRegistrar::class)->sync();

        $owner = Role::findOrCreate('Owner');
        $owner->syncPermissions(Permission::all());

        $admin = Role::findOrCreate('Admin');
        $admin->syncPermissions(Permission::all()->reject(
            fn (Permission $p) => $p->name === 'settings.manage_users' && false
        ));

        $manager = Role::findOrCreate('Manager');
        $manager->syncPermissions([
            'bidding.view', 'bidding.create', 'bidding.edit', 'bidding.archive', 'bidding.view_competitors',
            'projects.view', 'projects.create', 'projects.edit', 'projects.archive',
            'finance.view', 'finance.create', 'finance.edit',
            'hr.view', 'hr.create', 'hr.edit',
            'archive.view', 'archive.create', 'archive.edit',
        ]);

        $staff = Role::findOrCreate('Staff');
        $staff->syncPermissions([
            'bidding.view', 'bidding.create', 'bidding.edit',
            'projects.view', 'projects.create', 'projects.edit',
            'finance.view', 'finance.create',
            'hr.view', 'hr.create', 'hr.edit',
            'archive.view', 'archive.create',
        ]);

        $viewer = Role::findOrCreate('Viewer');
        $viewer->syncPermissions([
            'bidding.view',
            'projects.view',
            'finance.view',
            'hr.view',
            'archive.view',
        ]);
    }
}
