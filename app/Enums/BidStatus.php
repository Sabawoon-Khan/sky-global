<?php

namespace App\Enums;

enum BidStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';
}
