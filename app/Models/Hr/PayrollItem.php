<?php

namespace App\Models\Hr;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'personnel_type',
        'personnel_id',
        'project_id',
        'base_amount',
        'bonus',
        'deductions',
        'advance',
        'net_amount',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deductions' => 'decimal:2',
            'advance' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public static function calculateNetAmount(
        float $baseAmount,
        float $bonus = 0,
        float $deductions = 0,
        float $advance = 0,
    ): float {
        return max(0, round($baseAmount + $bonus - $deductions - $advance, 2));
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }
}
