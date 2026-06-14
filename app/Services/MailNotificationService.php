<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\ListingApprovedNotification;
use App\Notifications\ListingRejectedNotification;
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
        $listing->owner?->notify(new ListingApprovedNotification($listing->title));
    }

    public function sendListingRejected(Listing $listing): void
    {
        $listing->owner?->notify(new ListingRejectedNotification($listing->title, (string) $listing->rejection_reason));
    }
}
