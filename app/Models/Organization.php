<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Models\Procurement\ProcurementOpportunity;
use App\Models\Project\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasAttachments, SoftDeletes;

    protected $fillable = [
        'organization_type_id',
        'name',
        'tax_id',
        'address',
        'province',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function organizationType(): BelongsTo
    {
        return $this->belongsTo(OrganizationType::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(OrganizationContact::class);
    }

    public function procurementOpportunities(): HasMany
    {
        return $this->hasMany(ProcurementOpportunity::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
