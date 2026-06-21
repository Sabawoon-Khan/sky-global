<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Archive\ArchivedDocument;
use App\Models\Procurement\Bid;

trait GeneratesMisReferenceNumbers
{
    protected function generateBidNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "B-{$year}-";

        $last = Bid::withTrashed()
            ->where('bid_number', 'like', "{$prefix}%")
            ->orderByDesc('bid_number')
            ->value('bid_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    protected function generateArchiveReferenceNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "ARC-{$year}-";

        $last = ArchivedDocument::withTrashed()
            ->where('reference_number', 'like', "{$prefix}%")
            ->orderByDesc('reference_number')
            ->value('reference_number');

        $sequence = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
