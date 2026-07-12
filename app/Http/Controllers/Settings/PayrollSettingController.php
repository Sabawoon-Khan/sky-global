<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Models\Hr\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollSettingController extends Controller
{
    use AuthorizesMisPermissions;

    public function edit(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        return Inertia::render('settings/PayrollRules/Index', [
            'settings' => PayrollSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        $validated = $request->validate([
            'working_days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'absent_deduction_percent' => ['required', 'integer', 'min:0', 'max:200'],
            'sick_leave_pay_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'annual_leave_pay_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'casual_leave_pay_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'other_leave_pay_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        PayrollSetting::current()->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Payroll rules updated.',
        ]);

        return back();
    }
}
