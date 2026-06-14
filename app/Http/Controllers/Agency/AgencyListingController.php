<?php

namespace App\Http\Controllers\Agency;

use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgencyListingRequest;
use App\Http\Requests\UpdateAgencyListingRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgencyListingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        Gate::authorize('create', Listing::class);

        return Inertia::render('Agency/Listings/Create', [
            'translations' => __('app.create_agency_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function store(StoreAgencyListingRequest $request, ListingService $listingService): RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        Gate::authorize('create', Listing::class);

        $listingService->createForAgency($request->user()->agency, $request->validated());

        return redirect()->route('agency.index')->with('success', __('app.create_agency_listing.flash'));
    }

    public function edit(Request $request, Listing $listing): Response|RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        Gate::authorize('update', $listing);

        $listing->load('images');

        return Inertia::render('Agency/Listings/Edit', [
            'listing' => $listing->toFormArray(),
            'translations' => __('app.edit_agency_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function update(UpdateAgencyListingRequest $request, Listing $listing, ListingService $listingService): RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        Gate::authorize('update', $listing);

        $wasApproved = $listing->isApproved();

        try {
            $listingService->updateForAgency($listing, $request->validated());
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.edit_agency_listing.blocked'));
        }

        $flash = $wasApproved
            ? __('app.edit_agency_listing.flash_resubmitted')
            : __('app.edit_agency_listing.flash_updated');

        return redirect()->route('agency.index')->with('success', $flash);
    }

    public function destroy(Request $request, Listing $listing, ListingService $listingService): RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        Gate::authorize('delete', $listing);

        $listingService->delete($listing);

        return redirect()->route('agency.index')->with('success', __('app.agency_show.listings.flash_deleted'));
    }
}
