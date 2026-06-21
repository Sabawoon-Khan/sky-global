<?php

namespace App\Models\Equipment;

use App\Models\Project\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonnelEquipmentIssue extends Model
{
    protected $fillable = [
        'personnel_type',
        'personnel_id',
        'equipment_catalog_id',
        'project_id',
        'quantity',
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

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }

    public function equipmentCatalog(): BelongsTo
    {
        return $this->belongsTo(EquipmentCatalog::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PersonnelEquipmentReturn::class);
    }
}
