<?php

namespace App\Services;

use App\Models\Listing;

class ListingCleanupService
{
    public function __construct(private ListingImageService $imageService) {}

    /**
     * Permanently delete a rejected, soft-deleted listing along with its image
     * files and records. Bookmarks are removed automatically by the database
     * via the listing_id foreign key cascade. Does nothing when the listing is
     * not in the rejected + trashed state.
     */
    public function forceDeleteRejectedListing(Listing $listing): void
    {
        if (! $listing->isRejected() || ! $listing->trashed()) {
            return;
        }

        foreach ($listing->images as $image) {
            $this->imageService->delete($image);
        }

        $listing->forceDelete();
    }
}
