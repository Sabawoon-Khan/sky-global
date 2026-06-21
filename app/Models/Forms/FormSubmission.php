<?php

namespace App\Models\Forms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormSubmission extends Model
{
    protected $fillable = [
        'form_template_id',
        'submittable_type',
        'submittable_id',
        'status',
        'submitted_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class);
    }

    public function submittable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class);
    }
}
