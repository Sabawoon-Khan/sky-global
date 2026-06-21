<?php

namespace App\Models\Forms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormSubmissionValue extends Model
{
    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'value_text',
        'value_file_path',
    ];

    public function formSubmission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }
}
