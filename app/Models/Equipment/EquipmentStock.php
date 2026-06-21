<?php

namespace App\Models\Equipment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentStock extends Model
{
    protected $table = 'equipment_stock';

    protected $fillable = [
        'equipment_catalog_id',
        'quantity_on_hand',
        'quantity_reserved',
    ];

    public function equipmentCatalog(): BelongsTo
    {
        return $this->belongsTo(EquipmentCatalog::class);
    }
}
