<?php

namespace App\Models\Equipment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEquipmentReturn extends Model
{
    protected $fillable = [
        'project_equipment_issue_id',
        'quantity',
        'returned_at',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'date',
        ];
    }

    public function projectEquipmentIssue(): BelongsTo
    {
        return $this->belongsTo(ProjectEquipmentIssue::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
