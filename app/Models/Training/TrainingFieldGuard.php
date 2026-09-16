<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingFieldGuard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'father_name',
        'grandfather_name',
        'tazkira_number',
        'id_card_number',
        'site',
        'notes',
        'created_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reports(): BelongsToMany
    {
        return $this->belongsToMany(
            TrainingFieldReport::class,
            'training_field_report_guard',
            'training_field_guard_id',
            'training_field_report_id',
        );
    }
}
