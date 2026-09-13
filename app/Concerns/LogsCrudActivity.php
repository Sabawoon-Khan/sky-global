<?php

namespace App\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsCrudActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('crud')
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept($this->activityLogHiddenAttributes())
            ->setDescriptionForEvent(fn (string $eventName): string => $eventName);
    }

    /** @return list<string> */
    protected function activityLogHiddenAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];
    }

    public function activitySubjectLabel(): string
    {
        foreach (['name', 'title', 'code', 'invoice_number', 'bid_number', 'reference_number'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $first = $this->getAttribute('first_name');
        $last = $this->getAttribute('last_name');

        if (is_string($first) || is_string($last)) {
            $full = trim(($first ?? '').' '.($last ?? ''));

            if ($full !== '') {
                return $full;
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }
}
