<?php

namespace App\Models\Equipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCatalog extends Model
{
    protected $table = 'training_catalog';

    protected $fillable = [
        'name',
        'description',
        'validity_months',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function personnelTrainings(): HasMany
    {
        return $this->hasMany(PersonnelTraining::class);
    }
}
