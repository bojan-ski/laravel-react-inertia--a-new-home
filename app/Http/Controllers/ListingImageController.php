<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingImageRequest;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Services\ListingImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ListingImageController extends Controller
{
    public function store(StoreListingImageRequest $request, Listing $listing, ListingImageService $service): RedirectResponse
    {
        Gate::authorize('update', $listing);

        $service->upload($listing, $request->file('image'), $request->input('alt_text'));

        return redirect()->back()->with('success', __('app.listing_images.uploaded'));
    }

    public function destroy(Listing $listing, ListingImage $image, ListingImageService $service): RedirectResponse
    {
        Gate::authorize('update', $listing);

        abort_if($image->listing_id !== $listing->id, 404);

        $service->delete($image);

        return redirect()->back()->with('success', __('app.listing_images.deleted'));
    }
}
