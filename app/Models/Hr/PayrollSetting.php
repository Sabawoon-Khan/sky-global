<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = [
        'working_days_per_month',
        'absent_deduction_percent',
        'sick_leave_pay_percent',
        'annual_leave_pay_percent',
        'casual_leave_pay_percent',
        'other_leave_pay_percent',
    ];

    protected function casts(): array
    {
        return [
            'working_days_per_month' => 'integer',
            'absent_deduction_percent' => 'integer',
            'sick_leave_pay_percent' => 'integer',
            'annual_leave_pay_percent' => 'integer',
            'casual_leave_pay_percent' => 'integer',
            'other_leave_pay_percent' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'working_days_per_month' => 30,
            'absent_deduction_percent' => 100,
            'sick_leave_pay_percent' => 100,
            'annual_leave_pay_percent' => 100,
            'casual_leave_pay_percent' => 100,
            'other_leave_pay_percent' => 0,
        ]);
    }
}
