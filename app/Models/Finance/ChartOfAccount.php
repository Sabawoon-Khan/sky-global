<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function projectIncomes(): HasMany
    {
        return $this->hasMany(ProjectIncome::class, 'account_id');
    }

    public function projectExpenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'account_id');
    }

    public function generalExpenses(): HasMany
    {
        return $this->hasMany(GeneralExpense::class, 'account_id');
    }
}
