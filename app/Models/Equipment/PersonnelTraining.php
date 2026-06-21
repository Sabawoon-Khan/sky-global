<?php

namespace App\Models\Equipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonnelTraining extends Model
{
    protected $table = 'personnel_trainings';

    protected $fillable = [
        'personnel_type',
        'personnel_id',
        'training_catalog_id',
        'training_session_id',
        'completed_at',
        'expires_at',
        'certificate_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }

    public function trainingCatalog(): BelongsTo
    {
        return $this->belongsTo(TrainingCatalog::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }
}
