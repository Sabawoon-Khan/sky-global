<?php

namespace App\Models\Hr;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonnelPayrollAdjustment extends Model
{
    public const TYPE_BONUS = 'bonus';

    public const TYPE_DEDUCTION = 'deduction';

    public const TYPE_ADVANCE = 'advance';

    protected $fillable = [
        'personnel_type',
        'personnel_id',
        'project_id',
        'period_year',
        'period_month',
        'type',
        'amount',
        'notes',
        'payroll_item_id',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return array{bonus: float, deductions: float, advance: float}
     */
    public static function pendingTotalsForLine(
        string $personnelType,
        int $personnelId,
        ?int $projectId,
        int $year,
        int $month,
    ): array {
        $rows = static::query()
            ->pending()
            ->forPersonnelPeriod($personnelType, $personnelId, $year, $month)
            ->forProject($projectId)
            ->get();

        return [
            'bonus' => round((float) $rows->where('type', self::TYPE_BONUS)->sum('amount'), 2),
            'deductions' => round((float) $rows->where('type', self::TYPE_DEDUCTION)->sum('amount'), 2),
            'advance' => round((float) $rows->where('type', self::TYPE_ADVANCE)->sum('amount'), 2),
        ];
    }

    public static function markAppliedForPayrollItem(PayrollItem $item, int $year, int $month): void
    {
        static::query()
            ->pending()
            ->forPersonnelPeriod($item->personnel_type, $item->personnel_id, $year, $month)
            ->forProject($item->project_id)
            ->update([
                'payroll_item_id' => $item->id,
                'applied_at' => now(),
            ]);
    }

    public function scopePending($query)
    {
        return $query->whereNull('applied_at');
    }

    public function scopeForPersonnelPeriod($query, string $personnelType, int $personnelId, int $year, int $month)
    {
        return $query
            ->where('personnel_type', $personnelType)
            ->where('personnel_id', $personnelId)
            ->where('period_year', $year)
            ->where('period_month', $month);
    }

    public function scopeForProject($query, ?int $projectId)
    {
        if ($projectId === null) {
            return $query->whereNull('project_id');
        }

        return $query->where('project_id', $projectId);
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
