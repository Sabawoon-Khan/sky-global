<?php

namespace App\Models\Archive;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ArchivedDocumentLink extends Model
{
    protected $fillable = [
        'archived_document_id',
        'linkable_type',
        'linkable_id',
    ];

    public function archivedDocument(): BelongsTo
    {
        return $this->belongsTo(ArchivedDocument::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
