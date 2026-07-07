<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Services\ListingCleanupService;
use Illuminate\Console\Command;

class PurgeRejectedListings extends Command
{
    protected $signature = 'listings:purge-rejected';

    protected $description = 'Permanently delete rejected listings older than 7 days';

    public function handle(ListingCleanupService $cleanupService): int
    {
        $listings = Listing::onlyTrashed()
            ->where('status', ListingStatus::Rejected)
            ->whereNotNull('rejected_at')
            ->where('rejected_at', '<=', now()->subDays(7))
            ->get();

        foreach ($listings as $listing) {
            $cleanupService->forceDeleteRejectedListing($listing);
        }

        $this->info("Purged {$listings->count()} rejected listings.");

        return self::SUCCESS;
    }
}
