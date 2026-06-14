<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $listingTitle, public string $reason) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

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
