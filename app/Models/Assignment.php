<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\LogsCrudActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasAttachments, LogsCrudActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'description',
        'assigned_on',
        'reply_by',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_on' => 'date',
            'reply_by' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AssignmentRecipient::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(AssignmentReply::class)->latest();
    }
}
