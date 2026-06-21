<?php

namespace App\Models\Hr;

use App\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use HasAttachments, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'father_name',
        'original_address',
        'current_address',
        'phone',
        'email',
        'tazkira_number',
        'date_of_birth',
        'gender',
        'photo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(ContractorAgreement::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ContractorRate::class);
    }
}
