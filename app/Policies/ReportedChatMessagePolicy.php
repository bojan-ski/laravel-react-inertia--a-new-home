<?php

namespace App\Policies;

use App\Models\ReportedChatMessage;
use App\Models\User;

class ReportedChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function resolve(User $user, ReportedChatMessage $report): bool
    {
        return $user->isAdmin() && $report->canBeModerated();
    }

    public function suspendOffendingUser(User $user, ReportedChatMessage $report): bool
    {
        return $user->isAdmin() && $report->canBeModerated();
    }
}
