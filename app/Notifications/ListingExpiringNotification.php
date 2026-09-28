<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ListingExpiringNotification extends MailOnlyNotification
{
    public function __construct(
        public string $listingTitle,
        public string $expiresOn,
        public string $dashboardUrl,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.listing_expiring.subject'))
            ->greeting(__('app.emails.listing_expiring.greeting'))
            ->line(__('app.emails.listing_expiring.intro', ['title' => $this->listingTitle, 'date' => $this->expiresOn]))
            ->action(__('app.emails.listing_expiring.action'), $this->dashboardUrl)
            ->line(__('app.emails.listing_expiring.outro'));
    }
}
