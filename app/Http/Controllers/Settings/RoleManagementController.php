<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    use AuthorizesMisPermissions;

    /** @var list<string> */
    private array $protectedRoles = ['Owner'];

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.manage_users');

        return Inertia::render('settings/Roles/Index', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get(['id', 'name']),
            'permissions' => Permission::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];

        $role = Role::findOrCreate($validated['name']);
        $role->syncPermissions($permissions);

        return back()->with('success', 'Role created.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        $validated = $request->validated();
        $permissions = $validated['permissions'] ?? [];

        if (
            in_array($role->name, $this->protectedRoles, true)
            && $validated['name'] !== $role->name
        ) {
            return back()->withErrors(['name' => 'This role cannot be renamed.']);
        }

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($permissions);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        if (in_array($role->name, $this->protectedRoles, true)) {
            return back()->withErrors(['role' => 'This role cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Cannot delete a role that is assigned to users.']);
        }

        $role->delete();

        return back()->with('success', 'Role deleted.');
    }
}
