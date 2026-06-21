<?php

namespace App\Models\Project;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectDocument extends Model
{
    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'category',
        'direction',
        'title',
        'file_path',
        'document_date',
        'is_archived',
        'archived_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
