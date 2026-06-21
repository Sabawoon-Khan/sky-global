<?php

namespace App\Models\Equipment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelEquipmentReturn extends Model
{
    protected $fillable = [
        'personnel_equipment_issue_id',
        'quantity',
        'returned_at',
        'condition',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'date',
        ];
    }

    public function personnelEquipmentIssue(): BelongsTo
    {
        return $this->belongsTo(PersonnelEquipmentIssue::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
