<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttachmentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'requires_expiry',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_expiry' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function personnelAttachments(): HasMany
    {
        return $this->hasMany(PersonnelAttachment::class);
    }
}
