<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectListingRequest;
use App\Models\Listing;
use App\Services\ListingService;
use App\Services\MailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingModerationController extends Controller
{
    public function __construct(
        private ListingService $service,
        private MailNotificationService $mail,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', Listing::class);

        $listings = Listing::byStatus(ListingStatus::PendingReview)
            ->with(['owner:id,name,email', 'agency:id,name,email'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Listing $listing): array => $listing->toModerationArray());

        return Inertia::render('Admin/Listings/Index', [
            'listings' => $listings,
            'translations' => __('app.admin.listings'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function show(Listing $listing): Response
    {
        Gate::authorize('review', $listing);

        $listing->load(['images', 'owner:id,name,email', 'agency:id,name,email']);

        return Inertia::render('Admin/Listings/Show', [
            'listing' => $listing->toReviewArray(),
            'canModerate' => Gate::allows('approve', $listing),
            'translations' => __('app.admin.listings'),
            'listingTranslations' => __('app.listings.show'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
            'amenityLabels' => __('app.listings.amenities'),
        ]);
    }

    public function approve(Listing $listing): RedirectResponse
    {
        Gate::authorize('approve', $listing);

        try {
            $this->service->approve($listing);
        } catch (InvalidListingStatusTransitionException) {
            return back()->with('error', __('app.admin.listings.flash.approve_failed'));
        }

        $this->mail->sendListingApproved($listing);

        return back()->with('success', __('app.admin.listings.flash.approved'));
    }

    public function reject(RejectListingRequest $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('reject', $listing);

        try {
            $this->service->reject($listing, $request->validated('reason'));
        } catch (InvalidListingStatusTransitionException) {
            return back()->with('error', __('app.admin.listings.flash.reject_failed'));
        }

        $this->mail->sendListingRejected($listing);

        return back()->with('success', __('app.admin.listings.flash.rejected'));
    }
}
