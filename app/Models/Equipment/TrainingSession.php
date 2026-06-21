<?php

namespace App\Models\Equipment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    protected $fillable = [
        'training_catalog_id',
        'title',
        'session_date',
        'location',
        'instructor_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
        ];
    }

    public function trainingCatalog(): BelongsTo
    {
        return $this->belongsTo(TrainingCatalog::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function personnelTrainings(): HasMany
    {
        return $this->hasMany(PersonnelTraining::class);
    }
}
