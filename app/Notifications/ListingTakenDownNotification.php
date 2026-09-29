<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ListingTakenDownNotification extends MailOnlyNotification
{
    public function __construct(public string $listingTitle, public string $reason) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.listing_taken_down.subject'))
            ->greeting(__('app.emails.listing_taken_down.greeting'))
            ->line(__('app.emails.listing_taken_down.intro', ['title' => $this->listingTitle]))
            ->line(__('app.emails.listing_taken_down.reason', ['reason' => $this->reason]))
            ->line(__('app.emails.listing_taken_down.outro'));
    }
}
