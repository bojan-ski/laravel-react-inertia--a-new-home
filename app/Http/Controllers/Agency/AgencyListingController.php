<?php

namespace App\Http\Controllers\Agency;

use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgencyListingRequest;
use App\Http\Requests\UpdateAgencyListingRequest;
use App\Models\Listing;
use App\Services\ListingImageService;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgencyListingController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Listing::class);

        return Inertia::render('Agency/Listings/Create', [
            'translations' => __('app.create_agency_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
            'amenityLabels' => __('app.listings.amenities'),
            'countryOptions' => Listing::countryOptions(),
            'maxImages' => Listing::MAX_NEW_IMAGES,
        ]);
    }

    public function store(StoreAgencyListingRequest $request, ListingService $listingService, ListingImageService $imageService): RedirectResponse
    {
        Gate::authorize('create', Listing::class);

        $listing = $listingService->createForAgency($request->user()->agency, $request->validated());

        $imageService->uploadMany($listing, $request->file('images', []));

        return redirect()->route('agency.index')->with('success', __('app.create_agency_listing.flash'));
    }

    public function edit(Listing $listing): Response
    {
        Gate::authorize('update', $listing);

        $listing->load('images');

        return Inertia::render('Agency/Listings/Edit', [
            'listing' => $listing->toFormArray(),
            'translations' => __('app.edit_agency_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
            'amenityLabels' => __('app.listings.amenities'),
            'countryOptions' => Listing::countryOptions(),
        ]);
    }

    public function update(UpdateAgencyListingRequest $request, Listing $listing, ListingService $listingService): RedirectResponse
    {
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

    public function destroy(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $listingService->delete($listing);

        return redirect()->route('agency.index')->with('success', __('app.agency_show.listings.flash_deleted'));
    }

    public function close(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('close', $listing);

        try {
            $listingService->close($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.agency_show.listings.flash_close_failed'));
        }

        return redirect()->back()->with('success', __($listing->isRented()
            ? 'app.agency_show.listings.flash_closed_rented'
            : 'app.agency_show.listings.flash_closed_sold'));
    }

    public function reopen(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('reopen', $listing);

        try {
            $listingService->reopen($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.agency_show.listings.flash_reopen_failed'));
        }

        return redirect()->back()->with('success', __('app.agency_show.listings.flash_reopened'));
    }

    public function renew(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('renew', $listing);

        try {
            $listingService->renew($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.agency_show.listings.flash_renew_failed'));
        }

        return redirect()->back()->with('success', __('app.agency_show.listings.flash_renewed', [
            'date' => $listing->expires_at->format('F j, Y'),
        ]));
    }
}
