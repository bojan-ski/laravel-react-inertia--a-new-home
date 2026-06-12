<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingIndexRequest;
use App\Models\Listing;
use App\Services\ListingService;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    public function __construct(
        private ListingService $listingService,
    ) {}

    public function index(ListingIndexRequest $request): Response
    {
        $listings = $this->listingService->getPublicListings($request)
            ->through(fn (Listing $listing) => $listing->toCardArray());

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            'filters' => $request->filters(),
            'translations' => __('app.listings'),
        ]);
    }
}
