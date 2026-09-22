<?php

namespace App\Models\Finance;

use App\Concerns\HasAttachments;
use App\Concerns\LogsCrudActivity;
use App\Models\Organization;
use App\Models\Project\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasAttachments, LogsCrudActivity, SoftDeletes;

    public static function daysInPeriod(mixed $start, mixed $end): ?int
    {
        if (blank($start) || blank($end)) {
            return null;
        }

        $from = Carbon::parse($start)->startOfDay();
        $to = Carbon::parse($end)->startOfDay();

        if ($to->lt($from)) {
            return null;
        }

        return (int) $from->diffInDays($to) + 1;
    }

    public static function proratedLineTotal(
        float $unitPrice,
        float $quantity,
        int $days,
        mixed $periodStart = null,
        mixed $periodEnd = null,
        mixed $fallbackDate = null,
    ): float {
        $billDays = max(1, $days);

        if (filled($periodStart) && filled($periodEnd)) {
            $from = Carbon::parse($periodStart)->startOfDay();
            $to = Carbon::parse($periodEnd)->startOfDay();

            if ($to->gte($from)) {
                $periodDays = (int) $from->diffInDays($to) + 1;
                $billDays = min($billDays, $periodDays);
                $billTo = $from->copy()->addDays($billDays - 1);

                return round(
                    self::proratedByCalendarMonths($unitPrice, $quantity, $from, $billTo),
                    2,
                );
            }
        }

        $monthDays = filled($fallbackDate)
            ? Carbon::parse($fallbackDate)->daysInMonth
            : Carbon::now()->daysInMonth;

        return round(($unitPrice / $monthDays) * $quantity * $billDays, 2);
    }

    private static function proratedByCalendarMonths(
        float $unitPrice,
        float $quantity,
        Carbon $from,
        Carbon $to,
    ): float {
        $total = 0.0;
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $segmentEnd = $cursor->copy()->endOfMonth()->startOfDay();

            if ($segmentEnd->gt($to)) {
                $segmentEnd = $to->copy();
            }

            $daysInSegment = (int) $cursor->diffInDays($segmentEnd) + 1;
            $daysInMonth = $cursor->daysInMonth;
            $total += ($unitPrice / $daysInMonth) * $quantity * $daysInSegment;
            $cursor = $segmentEnd->copy()->addDay();
        }

        return $total;
    }

    protected $fillable = [
        'project_id',
        'organization_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'services',
        'period_start',
        'period_end',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
