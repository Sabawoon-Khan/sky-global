<?php

namespace App\Http\Controllers\Forms;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Forms\FormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FormTemplateController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        $templates = FormTemplate::query()
            ->withCount('fields')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('mis/forms/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:form_templates,slug'],
            'description' => ['nullable', 'string'],
            'context' => ['nullable', 'string', 'max:100'],
            'is_required_on_registration' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        FormTemplate::query()->create([
            ...$validated,
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
        ]);

        return back()->with('success', 'Form template created.');
    }

    public function update(Request $request, FormTemplate $formTemplate): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:form_templates,slug,'.$formTemplate->id],
            'description' => ['nullable', 'string'],
            'context' => ['nullable', 'string', 'max:100'],
            'is_required_on_registration' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $formTemplate->update($validated);

        return back()->with('success', 'Form template updated.');
    }
}
