<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\AgencyApprovedNotification;
use App\Notifications\AgencyChangesApprovedNotification;
use App\Notifications\AgencyChangesRejectedNotification;
use App\Notifications\AgencyRejectedNotification;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingExpiringNotification;
use App\Notifications\ListingRejectedNotification;
use App\Notifications\ListingTakenDownNotification;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Support\Facades\Notification;

class MailNotificationService
{
    public function sendUserSuspended(User $user): void
    {
        $user->notify(new UserSuspendedNotification);
    }

    public function sendAccountDeleted(string $name, string $email): void
    {
        Notification::route('mail', $email)->notify(new AccountDeletedNotification($name));
    }

    public function sendListingApproved(Listing $listing): void
    {
        $this->resolveListingRecipient($listing)?->notify(
            new ListingApprovedNotification($listing->title)
        );
    }

    public function sendListingRejected(Listing $listing): void
    {
        $this->resolveListingRecipient($listing)?->notify(
            new ListingRejectedNotification($listing->title, (string) $listing->rejection_reason)
        );
    }

    public function sendListingTakenDown(Listing $listing): void
    {
        $this->resolveListingRecipient($listing)?->notify(
            new ListingTakenDownNotification($listing->title, (string) $listing->rejection_reason)
        );
    }

    public function sendListingExpiring(Listing $listing): void
    {
        $this->resolveListingRecipient($listing)?->notify(new ListingExpiringNotification(
            $listing->title,
            $listing->expires_at->format('F j, Y'),
            route($listing->agency_id !== null ? 'agency.index' : 'profile.index'),
        ));
    }

    public function sendAgencyApproved(Agency $agency): void
    {
        $agency->user?->notify(new AgencyApprovedNotification($agency->name));
    }

    public function sendAgencyRejected(Agency $agency): void
    {
        $agency->user?->notify(new AgencyRejectedNotification($agency->name, (string) $agency->rejection_reason));
    }

    public function sendAgencyChangesApproved(Agency $agency): void
    {
        $agency->user?->notify(new AgencyChangesApprovedNotification($agency->name));
    }

    public function sendAgencyChangesRejected(Agency $agency, string $reason): void
    {
        $agency->user?->notify(new AgencyChangesRejectedNotification($agency->name, $reason));
    }

    private function resolveListingRecipient(Listing $listing): ?User
    {
        $listing->loadMissing(['owner', 'agency.user']);

        return $listing->owner ?? $listing->agency?->user;
    }
}
