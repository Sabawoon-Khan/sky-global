<?php

namespace App\Models\Finance;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseAllocation extends Model
{
    protected $fillable = [
        'general_expense_id',
        'project_id',
        'percentage',
        'allocated_amount',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'allocated_amount' => 'decimal:2',
        ];
    }

    public function generalExpense(): BelongsTo
    {
        return $this->belongsTo(GeneralExpense::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
