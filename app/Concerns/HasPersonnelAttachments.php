<?php

namespace App\Concerns;

use App\Models\Forms\PersonnelAttachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPersonnelAttachments
{
    public function personnelAttachments(): MorphMany
    {
        return $this->morphMany(PersonnelAttachment::class, 'personnel')->latest();
    }
}
