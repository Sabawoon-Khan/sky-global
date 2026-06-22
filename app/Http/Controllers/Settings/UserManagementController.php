<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserManagementRequest;
use App\Http\Requests\Settings\UpdateUserManagementRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.manage_users');

        $search = $request->string('search')->trim()->toString();

        $users = User::query()
            ->with('roles')
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('settings/Users/Index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function store(StoreUserManagementRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        $validated = $request->validated();
        $roles = $validated['roles'] ?? [];
        unset($validated['roles']);

        $user = User::query()->create([
            ...$validated,
            'email_verified_at' => now(),
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return back()->with('success', 'User created.');
    }

    public function update(UpdateUserManagementRequest $request, User $user): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        if ($user->id === $request->user()->id && $request->has('is_active') && ! $request->boolean('is_active')) {
            return back()->withErrors(['user' => 'You cannot disable your own account.']);
        }

        $validated = $request->validated();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }

        if (array_key_exists('is_active', $validated)) {
            if ($validated['is_active']) {
                $user->enable();
            } else {
                $user->disable($request->user());
            }
        }

        return back()->with('success', 'User updated.');
    }
}
