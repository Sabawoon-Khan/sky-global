<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Concerns\StoresOptionalAttachments;
use App\Http\Controllers\Concerns\StoresPersonnelAttachments;
use App\Http\Controllers\Controller;
use App\Models\Finance\Currency;
use App\Models\Forms\AttachmentType;
use App\Models\Hr\Contractor;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Hr\PersonnelPayrollAdjustment;
use App\Models\Project\Project;
use App\Models\Project\ProjectDeployment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller
{
    use AuthorizesMisPermissions, StoresOptionalAttachments, StoresPersonnelAttachments;

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

        return Inertia::render('mis/hr/Contractors/Create', [
            'projects' => $this->activeProjects(),
            'currencies' => $this->activeCurrencies(),
            'attachmentTypes' => $this->activeAttachmentTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.create');

        $validated = $request->validate([
            ...$this->contractorValidationRules(),
            ...$this->agreementValidationRules(),
            ...$this->rateValidationRules(),
            ...$this->personnelAttachmentValidationRules(),
        ]);

        unset($validated['agreements'], $validated['rates'], $validated['personnel_forms']);

        $contractor = Contractor::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);
        $contractor->logStatusChange($contractor->status, null, $request->user());
        $this->storeOptionalAttachment($request, $contractor);
        $this->syncAgreements($request, $contractor);
        $this->syncRates($request, $contractor);
        $this->storePersonnelAttachments($request, $contractor, 'contractor');

        return redirect()
            ->route('hr.contractors.show', $contractor)
            ->with('success', 'Contractor created.');
    }

    public function show(Request $request, Contractor $contractor): Response
    {
        $this->authorizePermission($request, 'hr.view');

        $contractor->load([
            'agreements' => fn ($q) => $q->orderByDesc('start_date'),
            'rates' => fn ($q) => $q->with('project')->orderByDesc('effective_from'),
            'attachments',
            'personnelAttachments.attachmentType',
            'statusChangeLogs' => fn ($q) => $q->with('changedBy:id,name')->latest(),
        ]);

        return Inertia::render('mis/hr/Contractors/Show', [
            'contractor' => $contractor,
            'attachmentTypes' => $this->activeAttachmentTypes(),
            'attendances' => PersonnelAttendance::query()
                ->where('personnel_type', Contractor::class)
                ->where('personnel_id', $contractor->id)
                ->with('project')
                ->latest()
                ->limit(24)
                ->get(),
            'payrollAdjustments' => PersonnelPayrollAdjustment::query()
                ->where('personnel_type', Contractor::class)
                ->where('personnel_id', $contractor->id)
                ->with('project')
                ->latest()
                ->limit(24)
                ->get(),
            'deployments' => ProjectDeployment::query()
                ->where('personnel_type', Contractor::class)
                ->where('personnel_id', $contractor->id)
                ->with('project')
                ->latest()
                ->get(),
        ]);
    }

    public function edit(Request $request, Contractor $contractor): Response
    {
        $this->authorizePermission($request, 'hr.edit');

        $contractor->load([
            'agreements',
            'rates.project',
            'personnelAttachments.attachmentType',
        ]);

        return Inertia::render('mis/hr/Contractors/Edit', [
            'contractor' => $contractor,
            'projects' => $this->activeProjects(),
            'currencies' => $this->activeCurrencies(),
            'attachmentTypes' => $this->activeAttachmentTypes(),
        ]);
    }

    public function update(Request $request, Contractor $contractor): RedirectResponse
    {
        $this->authorizePermission($request, 'hr.edit');

        $validated = $request->validate([
            ...$this->contractorValidationRules(true),
            ...$this->agreementValidationRules(true),
            ...$this->rateValidationRules(true),
            ...$this->personnelAttachmentValidationRules(),
        ]);

        unset($validated['agreements'], $validated['rates'], $validated['personnel_forms']);

        $oldStatus = $contractor->status;

        $contractor->update($validated);
        $this->storeOptionalAttachment($request, $contractor);
        $this->syncAgreements($request, $contractor);
        $this->syncRates($request, $contractor);
        $this->storePersonnelAttachments($request, $contractor, 'contractor');

        if (array_key_exists('status', $validated) && $validated['status'] !== $oldStatus) {
            $contractor->logStatusChange($validated['status'], $oldStatus, $request->user());
        }

        if (array_keys($validated) === ['status']) {
            return back()->with('success', 'Contractor status updated.');
        }

        return redirect()
            ->route('hr.contractors.show', $contractor)
            ->with('success', 'Contractor updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function contractorValidationRules(bool $updating = false): array
    {
        $requiredRule = $updating ? 'sometimes' : 'required';

        return [
            'first_name' => [$requiredRule, 'string', 'max:100'],
            'last_name' => [$requiredRule, 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'original_address' => ['nullable', 'string'],
            'current_address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tazkira_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'status' => ['nullable', 'string', 'in:active,inactive,terminated'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function agreementValidationRules(bool $updating = false): array
    {
        $rules = [
            'agreements' => ['nullable', 'array'],
            'agreements.*.agreement_number' => ['nullable', 'string', 'max:100'],
            'agreements.*.start_date' => ['required_with:agreements', 'date'],
            'agreements.*.end_date' => ['nullable', 'date'],
            'agreements.*.notes' => ['nullable', 'string'],
            'agreements.*.file' => ['nullable', 'file', 'max:10240'],
        ];

        if ($updating) {
            $rules['agreements.*.id'] = ['nullable', 'exists:contractor_agreements,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function rateValidationRules(bool $updating = false): array
    {
        $rules = [
            'rates' => ['nullable', 'array'],
            'rates.*.project_id' => ['nullable', 'exists:projects,id'],
            'rates.*.daily_rate' => ['nullable', 'numeric', 'min:0'],
            'rates.*.monthly_rate' => ['nullable', 'numeric', 'min:0'],
            'rates.*.currency' => ['nullable', 'string', 'size:3'],
            'rates.*.effective_from' => ['nullable', 'date'],
            'rates.*.effective_to' => ['nullable', 'date'],
        ];

        if ($updating) {
            $rules['rates.*.id'] = ['nullable', 'exists:contractor_rates,id'];
        }

        return $rules;
    }

    private function syncAgreements(Request $request, Contractor $contractor): void
    {
        if (! $request->has('agreements_synced')) {
            return;
        }

        $agreements = $request->input('agreements', []);

        if (! is_array($agreements)) {
            return;
        }

        $keptIds = [];

        foreach ($agreements as $index => $agreementData) {
            if (empty($agreementData['start_date'])) {
                continue;
            }

            $file = $request->file("agreements.{$index}.file");
            $payload = [
                'agreement_number' => $agreementData['agreement_number'] ?? null,
                'start_date' => $agreementData['start_date'],
                'end_date' => $agreementData['end_date'] ?? null,
                'notes' => $agreementData['notes'] ?? null,
            ];

            if ($file) {
                $payload['file_path'] = $file->store('contractor-agreements', 'local');
            }

            if (! empty($agreementData['id'])) {
                $agreement = $contractor->agreements()->find($agreementData['id']);

                if ($agreement) {
                    $agreement->update($payload);
                    $keptIds[] = $agreement->id;
                }

                continue;
            }

            $created = $contractor->agreements()->create($payload);
            $keptIds[] = $created->id;
        }

        if ($keptIds !== []) {
            $contractor->agreements()->whereNotIn('id', $keptIds)->delete();
        } else {
            $contractor->agreements()->delete();
        }
    }

    private function syncRates(Request $request, Contractor $contractor): void
    {
        if (! $request->has('rates_synced')) {
            return;
        }

        $rates = $request->input('rates', []);

        if (! is_array($rates)) {
            return;
        }

        $keptIds = [];

        foreach ($rates as $rateData) {
            $hasValues = ($rateData['daily_rate'] ?? null) !== null
                || ($rateData['monthly_rate'] ?? null) !== null
                || ! empty($rateData['project_id'])
                || ! empty($rateData['effective_from']);

            if (! $hasValues) {
                continue;
            }

            $payload = [
                'project_id' => $rateData['project_id'] ?? null,
                'daily_rate' => $rateData['daily_rate'] ?? null,
                'monthly_rate' => $rateData['monthly_rate'] ?? null,
                'currency' => $rateData['currency'] ?? 'USD',
                'effective_from' => $rateData['effective_from'] ?? null,
                'effective_to' => $rateData['effective_to'] ?? null,
            ];

            if (! empty($rateData['id'])) {
                $rate = $contractor->rates()->find($rateData['id']);

                if ($rate) {
                    $rate->update($payload);
                    $keptIds[] = $rate->id;
                }

                continue;
            }

            $created = $contractor->rates()->create($payload);
            $keptIds[] = $created->id;
        }

        if ($keptIds !== []) {
            $contractor->rates()->whereNotIn('id', $keptIds)->delete();
        } else {
            $contractor->rates()->delete();
        }
    }

    /**
     * @return Collection<int, Project>
     */
    private function activeProjects()
    {
        return Project::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    /**
     * @return list<string>
     */
    private function activeCurrencies(): array
    {
        return Currency::query()
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->pluck('code')
            ->all() ?: ['USD'];
    }

    /**
     * @return Collection<int, AttachmentType>
     */
    private function activeAttachmentTypes()
    {
        return AttachmentType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'requires_expiry', 'sort_order']);
    }
}
