<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreOrganizationTypeRequest;
use App\Http\Requests\Settings\UpdateOrganizationTypeRequest;
use App\Models\OrganizationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationTypeController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        return Inertia::render('settings/OrganizationTypes/Index', [
            'organizationTypes' => OrganizationType::query()
                ->withCount('organizations')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreOrganizationTypeRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        OrganizationType::query()->create($request->validated());

        return back()->with('success', 'Organization type created.');
    }

    public function update(UpdateOrganizationTypeRequest $request, OrganizationType $organizationType): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $organizationType->update($request->validated());

        return back()->with('success', 'Organization type updated.');
    }

    public function destroy(Request $request, OrganizationType $organizationType): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        if ($organizationType->organizations()->exists()) {
            return back()->withErrors(['organization_type' => 'Cannot delete a type that has organizations.']);
        }

        $organizationType->delete();

        return back()->with('success', 'Organization type deleted.');
    }
}
