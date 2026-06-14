<?php

namespace App\Enums;

enum AgencyStatus: string
{
    case PendingVerification = 'pending_verification';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Hidden = 'hidden';
}
