<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingIndexRequest;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Services\ListingService;
use App\Services\SavedSearchService;
use App\Services\Seo\PageSeo;
use App\Services\Seo\SeoHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingController extends Controller
{
    public function __construct(
        private ListingService $listingService,
        private SavedSearchService $savedSearchService,
    ) {}

    public function index(ListingIndexRequest $request, SeoHead $seoHead): Response
    {
        $filters = $request->filters();

        return Inertia::render('Listings/Index', [
            'listings' => $this->listingService->getPublicListings($request),
            'filters' => $filters,
            'canSaveSearch' => $request->user() === null || Gate::allows('create', SavedSearch::class),
            'suggestedSearchName' => $this->savedSearchService->suggestName($filters),
            'searchSummary' => $this->savedSearchService->summarize($filters),
            'saveSearchTranslations' => __('app.saved_searches.save'),
            'translations' => __('app.listings'),
            'head' => $seoHead->render(PageSeo::forPage(
                __('app.listings.title'),
                __('app.seo.pages.listings'),
                route('listings.index'),
            ), $request),
        ]);
    }

    public function show(Request $request, SeoHead $seoHead, Listing $listing): Response
    {
        abort_unless($listing->isActuallyPubliclyVisible(), 404);

        $listing->load(['images', 'owner', 'agency.user']);

        $user = $request->user();

        $payload = $listing->toShowArray();

        return Inertia::render('Listings/Show', [
            'listing' => $payload,
            'isBookmarked' => $user->hasBookmarked($listing),
            'canBookmark' => $user->can('bookmark', $listing),
            'canStartChat' => Gate::allows('start', [Conversation::class, $listing]),
            'canReport' => Gate::allows('report', $listing),
            'similarListings' => Inertia::defer(fn (): array => $this->listingService->getSimilarListings($listing, $user)),
            'mortgageDefaults' => config('listings.mortgage'),
            'reportTranslations' => __('app.listing_reports'),
            'translations' => __('app.listings'),
            'head' => $seoHead->render(new PageSeo(
                title: $listing->title,
                description: __('app.seo.description'),
                preloadImageUrl: $payload['images'][0]['url'] ?? null,
            ), $request),
        ]);
    }
}
