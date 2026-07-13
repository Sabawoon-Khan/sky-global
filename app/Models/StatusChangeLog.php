<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StatusChangeLog extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'from_status',
        'to_status',
        'changed_by',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
