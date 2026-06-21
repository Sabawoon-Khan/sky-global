<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'context',
        'is_required_on_registration',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required_on_registration' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
