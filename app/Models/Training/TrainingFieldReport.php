<?php

namespace App\Models\Training;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingFieldReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'report_date',
        'description',
        'trainer_name',
        'attachment_path',
        'original_filename',
        'created_by',
    ];

    protected $appends = ['attachment_url'];

    protected function casts(): array
    {
        return [
            'report_date' => 'date:Y-m-d',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function guards(): BelongsToMany
    {
        return $this->belongsToMany(
            TrainingFieldGuard::class,
            'training_field_report_guard',
            'training_field_report_id',
            'training_field_guard_id',
        );
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return route('training.field.reports.attachment', $this);
    }
}
