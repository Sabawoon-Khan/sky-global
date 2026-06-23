<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Finance\ProjectIncome;
use App\Models\Organization;
use App\Models\OrganizationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $search = $request->string('search')->trim()->toString();
        $typeId = $request->integer('organization_type_id');

        $organizations = Organization::query()
            ->with('organizationType')
            ->withCount(['projects', 'procurementOpportunities'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%");
            }))
            ->when($typeId, fn ($query) => $query->where('organization_type_id', $typeId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/organizations/Index', [
            'organizations' => $organizations,
            'organizationTypes' => OrganizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'total' => Organization::query()->count(),
                'active' => Organization::query()->where('is_active', true)->count(),
                'with_projects' => Organization::query()->has('projects')->count(),
            ],
            'filters' => [
                'search' => $search ?: null,
                'organization_type_id' => $typeId ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'bidding.create');

        return Inertia::render('mis/organizations/Create', [
            'organizationTypes' => OrganizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'provinces' => $this->afghanistanProvinces(),
        ]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.create');

        $organization = Organization::query()->create($request->validated());
        $this->storeOptionalAttachment($request, $organization);

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization created.');
    }

    public function show(Request $request, Organization $organization): Response
    {
        $this->authorizePermission($request, 'bidding.view');

        $organization->load([
            'organizationType',
            'contacts',
            'attachments',
            'procurementOpportunities' => fn ($q) => $q->latest(),
            'projects' => fn ($q) => $q->latest(),
        ]);

        $projectIds = $organization->projects->pluck('id');
        $totalIncome = ProjectIncome::query()
            ->whereIn('project_id', $projectIds)
            ->sum('amount_usd') ?: ProjectIncome::query()
            ->whereIn('project_id', $projectIds)
            ->sum('amount');

        $submittedProjects = $organization->projects->filter(
            fn ($p) => $p->bid_submitted_at !== null
                || in_array($p->status, ['submitted', 'won', 'lost', 'active', 'completed', 'closed'], true),
        );

        $completedProjects = $organization->projects->filter(
            fn ($p) => in_array($p->status, ['completed', 'closed', 'won'], true),
        );

        return Inertia::render('mis/organizations/Show', [
            'organization' => $organization,
            'organizationTypes' => OrganizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'stats' => [
                'projects_total' => $organization->projects->count(),
                'projects_completed' => $completedProjects->count(),
                'bids_submitted' => $submittedProjects->count(),
                'opportunities_total' => $organization->procurementOpportunities->count(),
                'total_contract_value' => (float) $organization->projects->sum('total_contract_value'),
                'total_income' => (float) $totalIncome,
                'contacts_count' => $organization->contacts->count(),
            ],
        ]);
    }

    public function edit(Request $request, Organization $organization): Response
    {
        $this->authorizePermission($request, 'bidding.edit');

        return Inertia::render('mis/organizations/Edit', [
            'organization' => $organization,
            'organizationTypes' => OrganizationType::query()->where('is_active', true)->orderBy('name')->get(),
            'provinces' => $this->afghanistanProvinces(),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.edit');

        $validated = $request->validated();
        $organization->update($validated);
        $this->storeOptionalAttachment($request, $organization);

        if (array_keys($validated) === ['is_active']) {
            $organization->refresh();

            return back()->with(
                'success',
                $organization->is_active ? 'Organization activated.' : 'Organization deactivated.',
            );
        }

        return redirect()
            ->route('organizations.show', $organization)
            ->with('success', 'Organization updated.');
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorizePermission($request, 'bidding.delete');

        if ($organization->projects()->exists() || $organization->procurementOpportunities()->exists()) {
            return back()->withErrors(['organization' => 'Cannot delete an organization with related records.']);
        }

        $organization->delete();

        return redirect()
            ->route('organizations.index')
            ->with('success', 'Organization deleted.');
    }

    /** @return list<string> */
    private function afghanistanProvinces(): array
    {
        return [
            'Badakhshan', 'Badghis', 'Baghlan', 'Balkh', 'Bamyan', 'Daykundi',
            'Farah', 'Faryab', 'Ghazni', 'Ghor', 'Helmand', 'Herat', 'Jowzjan',
            'Kabul', 'Kandahar', 'Kapisa', 'Khost', 'Kunar', 'Kunduz', 'Laghman',
            'Logar', 'Nangarhar', 'Nimroz', 'Nuristan', 'Paktia', 'Paktika',
            'Panjshir', 'Parwan', 'Samangan', 'Sar-e Pol', 'Takhar', 'Urozgan',
            'Wardak', 'Zabul',
        ];
    }
}
