<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'effective_date' => 'date',
        ];
    }
}
