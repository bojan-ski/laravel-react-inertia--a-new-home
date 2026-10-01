<?php

namespace App\Services;

use App\Enums\AgencyStatus;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Agency;
use App\Models\AgencyProfileChange;
use App\Models\ContactMessage;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;

class AdminStatsService
{
    /**
     * @return array{pendingListings: int, pendingAgencies: int, pendingAgencyChanges: int, reportedListings: int, reportedMessages: int, newContactMessages: int}
     */
    public function pendingCounts(): array
    {
        return [
            'pendingListings' => Listing::byStatus(ListingStatus::PendingReview)->count(),
            'pendingAgencies' => Agency::byStatus(AgencyStatus::PendingVerification)->count(),
            'pendingAgencyChanges' => AgencyProfileChange::count(),
            'reportedListings' => Listing::whereHas('openReports')->count(),
            'reportedMessages' => ReportedChatMessage::whereNull('resolved_at')->count(),
            'newContactMessages' => ContactMessage::whereNull('handled_at')->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        $listingCounts = Listing::withTrashed()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $userCounts = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $pending = $this->pendingCounts();

        return [
            'totalListings' => (int) $listingCounts->sum(),
            'pendingListings' => (int) $listingCounts->get(ListingStatus::PendingReview->value, 0),
            'approvedListings' => (int) $listingCounts->get(ListingStatus::Approved->value, 0),
            'rejectedListings' => (int) $listingCounts->get(ListingStatus::Rejected->value, 0),
            'closedListings' => (int) $listingCounts->get(ListingStatus::Sold->value, 0)
                + (int) $listingCounts->get(ListingStatus::Rented->value, 0),
            'totalRegularUsers' => (int) $userCounts->get(UserRole::RegularUser->value, 0),
            'totalAgencyUsers' => (int) $userCounts->get(UserRole::Agency->value, 0),
            'totalAdmins' => (int) $userCounts->get(UserRole::Admin->value, 0),
            'pendingAgencies' => $pending['pendingAgencies'],
            'approvedAgencies' => Agency::byStatus(AgencyStatus::Approved)->count(),
            'pendingAgencyChanges' => $pending['pendingAgencyChanges'],
            'reportedListings' => $pending['reportedListings'],
            'reportedMessages' => $pending['reportedMessages'],
            'newContactMessages' => $pending['newContactMessages'],
        ];
    }
}
