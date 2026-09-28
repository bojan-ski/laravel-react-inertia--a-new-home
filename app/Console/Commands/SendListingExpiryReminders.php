<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Services\ListingService;
use App\Services\MailNotificationService;
use Illuminate\Console\Command;

class SendListingExpiryReminders extends Command
{
    protected $signature = 'listings:send-expiry-reminders';

    protected $description = 'Email owners whose approved listings expire within the next 7 days';

    public function __construct(
        private readonly ListingService $listingService,
        private readonly MailNotificationService $mailNotificationService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $listings = Listing::dueForExpiryReminder()
            ->ownerNotSuspended()
            ->with(['owner', 'agency.user'])
            ->get();

        foreach ($listings as $listing) {
            $this->mailNotificationService->sendListingExpiring($listing);
            $this->listingService->markExpiryReminderSent($listing);
        }

        $this->info("Sent {$listings->count()} expiry reminders.");

        return self::SUCCESS;
    }
}
