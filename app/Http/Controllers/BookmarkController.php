<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\BookmarkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookmarkController extends Controller
{
    public function __construct(
        private BookmarkService $bookmarkService,
    ) {}

    public function toggle(Request $request, Listing $listing): RedirectResponse
    {
        $user = $request->user();

        if ($this->bookmarkService->hasBookmarked($user, $listing)) {
            Gate::authorize('removeBookmark', $listing);

            $this->bookmarkService->remove($user, $listing);

            return redirect()->back()->with('success', __('app.bookmarks.flash_removed'));
        }

        abort_unless($listing->isActuallyPubliclyVisible(), 404);
        Gate::authorize('bookmark', $listing);

        $bookmarked = $this->bookmarkService->toggle($user, $listing);

        return redirect()->back()->with('success', __($bookmarked ? 'app.bookmarks.flash_added' : 'app.bookmarks.flash_removed'));
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $listings = $this->bookmarkService->getBookmarkedListings($user)
            ->map(fn (Listing $listing) => [
                ...$listing->toCardArray(),
                'isBookmarked' => true,
                'canBookmark' => $user->can('bookmark', $listing),
            ]);

        $closedListings = $this->bookmarkService->getClosedBookmarkedListings($user)
            ->map(fn (Listing $listing) => [
                ...$listing->toBookmarkCardArray(),
                'isBookmarked' => true,
                'canBookmark' => $user->can('removeBookmark', $listing),
            ]);

        return Inertia::render('Bookmarks/Index', [
            'listings' => $listings,
            'closedListings' => $closedListings,
            'translations' => __('app.bookmarks'),
            'listingTranslations' => __('app.listings'),
        ]);
    }
}
