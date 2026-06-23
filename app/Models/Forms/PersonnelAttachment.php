<?php

namespace App\Models\Forms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PersonnelAttachment extends Model
{
    protected $appends = ['download_url'];

    protected $fillable = [
        'personnel_type',
        'personnel_id',
        'attachment_type_id',
        'file_path',
        'issued_at',
        'expires_at',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function personnel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'personnel_type', 'personnel_id');
    }

    public function attachmentType(): BelongsTo
    {
        return $this->belongsTo(AttachmentType::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('forms.personnel-attachments.download', $this);
    }
}
