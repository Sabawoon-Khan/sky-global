<?php

namespace App\Services;

use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSalary;
use App\Models\Hr\PayrollSetting;
use App\Models\Hr\PersonnelAttendance;
use App\Models\Project\ProjectDeployment;
use Illuminate\Support\Carbon;

class PayrollCalculationService
{
    /**
     * @return array{base_amount: float, absence_deduction: float, currency: string}
     */
    public function calculateFromAttendance(PersonnelAttendance $attendance): array
    {
        $settings = PayrollSetting::current();
        $workingDays = max(1, (int) $settings->working_days_per_month);

        [$monthlyRate, $currency] = $this->resolveMonthlyRate($attendance);

        if ($monthlyRate <= 0) {
            return [
                'base_amount' => 0.0,
                'absence_deduction' => 0.0,
                'currency' => $currency,
            ];
        }

        $dailyRate = $monthlyRate / $workingDays;

        $paidDays =
            (float) $attendance->days_present
            + (float) $attendance->days_sick_leave * ($settings->sick_leave_pay_percent / 100)
            + (float) $attendance->days_annual_leave * ($settings->annual_leave_pay_percent / 100)
            + (float) $attendance->days_casual_leave * ($settings->casual_leave_pay_percent / 100)
            + (float) $attendance->days_other * ($settings->other_leave_pay_percent / 100);

        $baseAmount = round($dailyRate * $paidDays, 2);

        $absenceDeduction = round(
            $dailyRate * (float) $attendance->days_absent * ($settings->absent_deduction_percent / 100),
            2,
        );

        return [
            'base_amount' => max(0, $baseAmount),
            'absence_deduction' => $absenceDeduction,
            'currency' => $currency,
        ];
    }

    /**
     * @return array{0: float, 1: string}
     */
    private function resolveMonthlyRate(PersonnelAttendance $attendance): array
    {
        if ($attendance->project_id !== null) {
            $deployment = ProjectDeployment::query()
                ->where('project_id', $attendance->project_id)
                ->where('personnel_type', $attendance->personnel_type)
                ->where('personnel_id', $attendance->personnel_id)
                ->where('status', 'active')
                ->where(function ($query) use ($attendance) {
                    $periodStart = Carbon::create($attendance->year, $attendance->month, 1);
                    $periodEnd = $periodStart->copy()->endOfMonth();

                    $query->where('start_date', '<=', $periodEnd)
                        ->where(function ($inner) use ($periodStart) {
                            $inner->whereNull('end_date')
                                ->orWhere('end_date', '>=', $periodStart);
                        });
                })
                ->orderByDesc('start_date')
                ->first();

            if ($deployment?->monthly_rate) {
                return [
                    (float) $deployment->monthly_rate,
                    $deployment->currency ?? 'AFN',
                ];
            }
        }

        if ($attendance->personnel_type === Employee::class) {
            $salary = EmployeeSalary::query()
                ->where('employee_id', $attendance->personnel_id)
                ->where(function ($query) use ($attendance) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', "{$attendance->year}-{$attendance->month}-01");
                })
                ->orderByDesc('effective_from')
                ->first();

            if ($salary) {
                return [
                    (float) $salary->amount,
                    $salary->currency ?? 'AFN',
                ];
            }
        }

        if ($attendance->personnel_type === Contractor::class) {
            return [0.0, 'AFN'];
        }

        return [0.0, 'AFN'];
    }
}
