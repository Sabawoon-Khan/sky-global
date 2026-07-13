<?php

namespace App\Models\Hr;

use App\Concerns\HasAttachments;
use App\Concerns\HasPersonnelAttachments;
use App\Concerns\HasStatusChangeLogs;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasAttachments, HasPersonnelAttachments, HasStatusChangeLogs, SoftDeletes;

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
        'is_permanent',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_permanent' => 'boolean',
        ];
    }

    public function scopePermanent($query)
    {
        return $query->where('is_permanent', true);
    }

    public function scopeProjectBased($query)
    {
        return $query->where('is_permanent', false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobDetails(): HasMany
    {
        return $this->hasMany(EmployeeJobDetail::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }
}
