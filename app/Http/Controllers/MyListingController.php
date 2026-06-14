<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Listing;
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

    public function store(StoreListingRequest $request, ListingService $listingService): RedirectResponse
    {
        $listingService->create($request->user(), $request->validated());

        return redirect()->route('profile.index')->with('success', __('app.create_listing.flash'));
    }
}
