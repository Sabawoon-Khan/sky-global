<?php

namespace App\Models\Equipment;

use App\Models\Project\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEquipmentIssue extends Model
{
    protected $fillable = [
        'project_id',
        'equipment_catalog_id',
        'quantity',
        'quantity_returned',
        'issued_at',
        'issued_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function equipmentCatalog(): BelongsTo
    {
        return $this->belongsTo(EquipmentCatalog::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function quantityOutstanding(): int
    {
        return max(0, (int) $this->quantity - (int) $this->quantity_returned);
    }
}
