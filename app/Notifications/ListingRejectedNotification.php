<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ListingRejectedNotification extends MailOnlyNotification
{
    public function __construct(public string $listingTitle, public string $reason) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.listing_rejected.subject'))
            ->greeting(__('app.emails.listing_rejected.greeting'))
            ->line(__('app.emails.listing_rejected.intro', ['title' => $this->listingTitle]))
            ->line(__('app.emails.listing_rejected.reason', ['reason' => $this->reason]))
            ->line(__('app.emails.listing_rejected.outro'));
    }
}
