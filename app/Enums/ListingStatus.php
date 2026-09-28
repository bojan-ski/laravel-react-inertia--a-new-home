<?php

namespace App\Enums;

enum ListingStatus: string
{
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Hidden = 'hidden';
    case Sold = 'sold';
    case Rented = 'rented';
}
