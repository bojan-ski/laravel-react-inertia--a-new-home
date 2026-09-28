<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Services\ListingImageService;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MyListingController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Listing::class);

        return Inertia::render('Listings/Create', [
            'translations' => __('app.create_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function store(StoreListingRequest $request, ListingService $listingService, ListingImageService $imageService): RedirectResponse
    {
        $listing = $listingService->create($request->user(), $request->validated());

        $imageService->uploadMany($listing, $request->file('images', []));

        return redirect()->route('profile.index')->with('success', __('app.create_listing.flash'));
    }

    public function edit(Listing $listing): Response
    {
        Gate::authorize('update', $listing);

        $listing->load('images');

        return Inertia::render('Listings/Edit', [
            'listing' => $listing->toFormArray(),
            'translations' => __('app.edit_listing'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function update(UpdateListingRequest $request, Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $wasApproved = $listing->isApproved();

        try {
            $listingService->update($listing, $request->validated());
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.edit_listing.blocked'));
        }

        $flash = $wasApproved ? __('app.edit_listing.flash_resubmitted') : __('app.edit_listing.flash_updated');

        return redirect()->route('profile.index')->with('success', $flash);
    }

    public function destroy(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $listingService->delete($listing);

        return redirect()->route('profile.index')->with('success', __('app.profile.flash_deleted'));
    }

    public function close(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('close', $listing);

        try {
            $listingService->close($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.profile.flash_close_failed'));
        }

        return redirect()->back()->with('success', __($listing->isRented() ? 'app.profile.flash_closed_rented' : 'app.profile.flash_closed_sold'));
    }

    public function reopen(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('reopen', $listing);

        try {
            $listingService->reopen($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.profile.flash_reopen_failed'));
        }

        return redirect()->back()->with('success', __('app.profile.flash_reopened'));
    }

    public function renew(Listing $listing, ListingService $listingService): RedirectResponse
    {
        Gate::authorize('renew', $listing);

        try {
            $listingService->renew($listing);
        } catch (InvalidListingStatusTransitionException) {
            return redirect()->back()->with('error', __('app.profile.flash_renew_failed'));
        }

        return redirect()->back()->with('success', __('app.profile.flash_renewed', [
            'date' => $listing->expires_at->format('F j, Y'),
        ]));
    }
}
