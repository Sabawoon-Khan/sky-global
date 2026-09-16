<?php

namespace App\Services;

use App\Models\Finance\Invoice;
use App\Models\Finance\Quotation;
use App\Models\Training\TrainingFieldReport;
use App\Models\Training\TrainingGuard;
use App\Models\Training\TrainingMinistryPayment;

class DocumentNumberService
{
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

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    public static function nextQuoteNumber(): string
    {
        $latest = Quotation::query()
            ->withTrashed()
            ->where('quote_number', 'like', 'SG%')
            ->orderByDesc('quote_number')
            ->lockForUpdate()
            ->value('quote_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/SG0*(\d+)/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return 'SG'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
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
}
