<?php

namespace App\Models\Archive;

use App\Models\Organization;
use App\Models\Procurement\Bid;
use App\Models\Project\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchivedDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_number',
        'title',
        'description',
        'direction',
        'document_category_id',
        'organization_id',
        'project_id',
        'bid_id',
        'file_path',
        'original_filename',
        'file_size',
        'document_date',
        'received_at',
        'sent_at',
        'uploaded_by',
        'tags',
        'is_archived',
        'version',
        'replaces_id',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'received_at' => 'datetime',
            'sent_at' => 'datetime',
            'tags' => 'array',
            'is_archived' => 'boolean',
        ];
    }

    public function documentCategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ArchivedDocumentLink::class);
    }
}
