<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingImageRequest;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ListingImageService;
use App\Services\ListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ListingImageController extends Controller
{
    public function __construct(private ListingService $listings) {}

    public function store(StoreListingImageRequest $request, Listing $listing, ListingImageService $service): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $service->upload($listing, $request->file('image'), $request->input('alt_text'));

        $resubmitted = $this->listings->returnToReviewAfterPhotoChange($listing);

        return redirect()->back()->with('success', __($resubmitted ? 'app.listing_images.uploaded_resubmitted' : 'app.listing_images.uploaded'));
    }

    public function destroy(Listing $listing, ListingImage $image, ListingImageService $service): RedirectResponse
    {
        Gate::authorize('update', $listing);

        abort_if($image->listing_id !== $listing->id, 404);

        $service->delete($image);

        $resubmitted = $this->listings->returnToReviewAfterPhotoChange($listing);

        return redirect()->back()->with('success', __($resubmitted ? 'app.listing_images.deleted_resubmitted' : 'app.listing_images.deleted'));
    }
}
