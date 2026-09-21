<?php

namespace App\Services;

use App\Models\Finance\Invoice;
use App\Models\Finance\Quotation;
use App\Models\Project\Project;
use App\Models\Training\TrainingFieldReport;
use App\Models\Training\TrainingGuard;
use App\Models\Training\TrainingMinistryPayment;
use Illuminate\Support\Collection;

class DocumentNumberService
{
    public const INVOICE_SEQUENCE_START = 1012;

    public static function resolve(?string $provided, callable $next): string
    {
        $value = is_string($provided) ? trim($provided) : '';

        return $value !== '' ? $value : $next();
    }

    public static function nextInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "SSGSC-{$year}-";

        $latest = Invoice::query()
            ->withTrashed()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->value('invoice_number');

        $sequence = self::INVOICE_SEQUENCE_START;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = max(self::INVOICE_SEQUENCE_START, ((int) $matches[1]) + 1);
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public static function nextProjectCode(): string
    {
        $year = now()->format('Y');
        $prefix = "SSGSC-{$year}-";

        $codes = Project::query()
            ->withTrashed()
            ->where(function ($query) use ($year) {
                $query->where('code', 'like', "SSGSC-{$year}-%")
                    ->orWhere('code', 'like', "GS-{$year}-%");
            })
            ->pluck('code');

        return $prefix.str_pad((string) self::nextSequence($codes), 4, '0', STR_PAD_LEFT);
    }

    public static function nextQuoteNumber(): string
    {
        $numbers = Quotation::query()
            ->withTrashed()
            ->where(function ($query) {
                $query->where('quote_number', 'like', 'SSGSC%')
                    ->orWhere('quote_number', 'like', 'SG%');
            })
            ->lockForUpdate()
            ->pluck('quote_number');

        return 'SSGSC-'.str_pad((string) self::nextSequence($numbers), 3, '0', STR_PAD_LEFT);
    }

    public static function nextTrainingCertificateNumber(): string
    {
        $year = now()->year;
        $prefix = "TRN-{$year}-";

        $latest = TrainingGuard::query()
            ->withTrashed()
            ->where('certificate_number', 'like', $prefix.'%')
            ->orderByDesc('certificate_number')
            ->lockForUpdate()
            ->value('certificate_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function nextMinistryPaymentNumber(): string
    {
        $year = now()->year;
        $prefix = "MPD-{$year}-";

        $latest = TrainingMinistryPayment::query()
            ->withTrashed()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('reference_number')
            ->lockForUpdate()
            ->value('reference_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function nextFieldTrainingReportNumber(): string
    {
        $year = now()->year;
        $prefix = "FTR-{$year}-";

        $latest = TrainingFieldReport::query()
            ->withTrashed()
            ->where('reference_number', 'like', $prefix.'%')
            ->orderByDesc('reference_number')
            ->lockForUpdate()
            ->value('reference_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /** @param  Collection<int, mixed>|array<int, mixed>  $values */
    private static function nextSequence(Collection|array $values): int
    {
        $sequence = 1;

        foreach ($values as $value) {
            if (is_string($value) && preg_match('/(\d+)$/', $value, $matches) === 1) {
                $sequence = max($sequence, ((int) $matches[1]) + 1);
            }
        }

        return $sequence;
    }
}
