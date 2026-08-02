<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ListingApprovedNotification extends MailOnlyNotification
{
    public function __construct(public string $listingTitle) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.listing_approved.subject'))
            ->greeting(__('app.emails.listing_approved.greeting'))
            ->line(__('app.emails.listing_approved.intro', ['title' => $this->listingTitle]))
            ->line(__('app.emails.listing_approved.outro'));
    }
}
