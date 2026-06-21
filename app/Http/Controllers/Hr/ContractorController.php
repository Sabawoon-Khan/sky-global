<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Controller;
use App\Models\Hr\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $contractors = Contractor::query()
            ->when($search, fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('mis/hr/Contractors/Index', [
            'contractors' => $contractors,
            'filters' => [
                'search' => $search ?: null,
                'status' => $status ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, 'hr.create');

        return Inertia::render('mis/hr/Contractors/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'original_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'string', 'in:active,inactive,terminated'],
        ]);

        $contractor = Contractor::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);
        $this->storeOptionalAttachment($request, $contractor);

        return redirect()
            ->route('hr.contractors.show', $contractor)
            ->with('success', 'Contractor created.');
    }

    public function show(Request $request, Contractor $contractor): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $contractor->load(['agreements', 'rates', 'attachments']);

        return Inertia::render('mis/hr/Contractors/Show', [
            'contractor' => $contractor,
        ]);
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'original_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'string', 'in:active,inactive,terminated'],
        ]);

        $contractor->update($validated);
        $this->storeOptionalAttachment($request, $contractor);

        return back()->with('success', 'Contractor updated.');
    }
}
