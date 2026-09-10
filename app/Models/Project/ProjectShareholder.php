<?php

namespace App\Models\Project;

use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectShareholder extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'phone',
        'email',
        'share_percent',
        'invested_amount',
        'returned_amount',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'share_percent' => 'decimal:2',
            'invested_amount' => 'decimal:2',
            'returned_amount' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ProjectShareholderTransaction::class)->latest('transaction_date');
    }

    public function outstandingAmount(): float
    {
        return max(0, (float) $this->invested_amount - (float) $this->returned_amount);
    }
}
