<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingIndexRequest;
use App\Models\Conversation;
use App\Models\Listing;
use App\Services\ListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function show(Request $request, Listing $listing): Response
    {
        abort_unless($listing->isActuallyPubliclyVisible(), 404);

        $listing->load('images');

        $user = $request->user();

        return Inertia::render('Listings/Show', [
            'listing' => $listing->toShowArray(),
            'isBookmarked' => method_exists($user, 'hasBookmarked') && $user->hasBookmarked($listing),
            'canStartChat' => Gate::allows('start', [Conversation::class, $listing]),
            'translations' => __('app.listings'),
        ]);
    }
}
