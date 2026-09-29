<?php

namespace App\Enums;

enum ListingReportReason: string
{
    case Scam = 'scam';
    case IncorrectInfo = 'incorrect_info';
    case NoLongerAvailable = 'no_longer_available';
    case Duplicate = 'duplicate';
    case Offensive = 'offensive';
    case Other = 'other';
}
