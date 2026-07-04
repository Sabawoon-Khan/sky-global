<?php

namespace App\Models;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageBackup extends Model
{
    protected $fillable = [
        'type',
        'filename',
        'path',
        'disk',
        'file_size',
        'status',
        'trigger',
        'error_message',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => BackupType::class,
            'status' => BackupStatus::class,
            'trigger' => BackupTrigger::class,
            'file_size' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
