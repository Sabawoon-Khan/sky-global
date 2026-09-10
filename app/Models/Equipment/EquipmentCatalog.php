<?php

namespace App\Models\Equipment;

use App\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipmentCatalog extends Model
{
    use HasAttachments;

    protected $table = 'equipment_catalog';

    protected $fillable = [
        'name',
        'sku',
        'category',
        'unit',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stock(): HasOne
    {
        return $this->hasOne(EquipmentStock::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(PersonnelEquipmentIssue::class);
    }

    public function projectIssues(): HasMany
    {
        return $this->hasMany(ProjectEquipmentIssue::class);
    }
}
