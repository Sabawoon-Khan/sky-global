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
        $currentUserId = $request->user()->id;

        $users = User::query()
            ->with([
                'roles',
                'statusChangeLogs' => fn ($q) => $q->with('changedBy:id,name')->latest(),
            ])
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $activityUserIds = User::idsWithSystemActivity($users->getCollection()->modelKeys());

        $users->through(function (User $user) use ($currentUserId, $activityUserIds) {
            $user->setAttribute(
                'can_delete',
                $user->id !== $currentUserId && ! in_array($user->id, $activityUserIds, true),
            );

            return $user;
        });

        return Inertia::render('settings/Users/Index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.manage_users');

        return Inertia::render('settings/Users/Create', [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
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

        $user->logStatusChange('active', null, $request->user());

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'User created.');
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
                $user->enable($request->user());
            } else {
                $user->disable($request->user());
            }
        }

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);

            return back()->with('success', 'Password updated.');
        }

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.manage_users');

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->hasSystemActivity()) {
            return back()->withErrors(['user' => 'Cannot delete a user that has activity in the system.']);
        }

        $user->statusChangeLogs()->delete();
        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
