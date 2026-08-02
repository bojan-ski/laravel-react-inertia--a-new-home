<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Listing;
use App\Models\ReportedChatMessage;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $listingCounts = Listing::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $userCounts = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $stats = [
            'totalListings' => (int) $listingCounts->sum(),
            'pendingListings' => (int) $listingCounts->get(ListingStatus::PendingReview->value, 0),
            'approvedListings' => (int) $listingCounts->get(ListingStatus::Approved->value, 0),
            'rejectedListings' => (int) $listingCounts->get(ListingStatus::Rejected->value, 0),
            'totalRegularUsers' => (int) $userCounts->get(UserRole::RegularUser->value, 0),
            'totalAgencyUsers' => (int) $userCounts->get(UserRole::Agency->value, 0),
            'totalAdmins' => (int) $userCounts->get(UserRole::Admin->value, 0),
            'pendingAgencies' => Agency::byStatus(AgencyStatus::PendingVerification)->count(),
            'approvedAgencies' => Agency::byStatus(AgencyStatus::Approved)->count(),
            'reportedMessages' => ReportedChatMessage::whereNull('resolved_at')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'translations' => __('app.admin.dashboard'),
        ]);
    }
}
