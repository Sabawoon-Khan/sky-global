<?php

namespace App\Models\Project;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectShareholderTransaction extends Model
{
    public const TYPE_CONTRIBUTION = 'contribution';

    public const TYPE_DISTRIBUTION = 'distribution';

    protected $fillable = [
        'project_shareholder_id',
        'type',
        'amount',
        'currency',
        'transaction_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function shareholder(): BelongsTo
    {
        return $this->belongsTo(ProjectShareholder::class, 'project_shareholder_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
