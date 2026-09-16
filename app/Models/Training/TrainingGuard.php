<?php

namespace App\Models\Training;

use App\Concerns\HasStatusChangeLogs;
use App\Concerns\LogsCrudActivity;
use App\Enums\TrainingPath;
use App\Enums\TrainingStatus;
use App\Models\Hr\Contractor;
use App\Models\Hr\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingGuard extends Model
{
    use HasStatusChangeLogs, LogsCrudActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'father_name',
        'grandfather_name',
        'tazkira_number',
        'id_card_number',
        'start_date',
        'end_date',
        'batch_number',
        'status',
        'training_path',
        'ministry_period_start',
        'ministry_period_end',
        'fee_number',
        'fee_amount',
        'ministry_payment_id',
        'company_trainer',
        'company_location',
        'certificate_number',
        'certificate_issued_at',
        'certificate_path',
        'certificate_original_filename',
        'notes',
        'created_by',
        'employee_id',
        'contractor_id',
    ];

    protected $appends = ['certificate_url'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'ministry_period_start' => 'date:Y-m-d',
            'ministry_period_end' => 'date:Y-m-d',
            'fee_amount' => 'decimal:2',
            'certificate_issued_at' => 'date:Y-m-d',
        ];
    }

    public function ministryPayment(): BelongsTo
    {
        return $this->belongsTo(TrainingMinistryPayment::class, 'ministry_payment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function isRegistered(): bool
    {
        return $this->status === TrainingStatus::Registered->value;
    }

    public function canAssignCompany(): bool
    {
        return $this->isRegistered();
    }

    public function canSendToMinistry(): bool
    {
        return $this->isRegistered();
    }

    public function canComplete(): bool
    {
        return in_array($this->status, TrainingStatus::inTraining(), true);
    }

    public function canIssueCertificate(): bool
    {
        return in_array($this->status, TrainingStatus::certifiable(), true);
    }

    public function isCertified(): bool
    {
        return $this->status === TrainingStatus::Certified->value;
    }

    public function trainingPathLabel(): ?string
    {
        return match ($this->training_path) {
            TrainingPath::Ministry->value => 'Public Protection Deputy',
            TrainingPath::Company->value => 'Company training',
            default => null,
        };
    }

    public function getCertificateUrlAttribute(): ?string
    {
        if (! $this->certificate_path) {
            return null;
        }

        return route('training.guards.certificate.download', $this);
    }
}
