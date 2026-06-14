<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListingApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $listingTitle) {}

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
            ->subject(__('app.emails.listing_approved.subject'))
            ->greeting(__('app.emails.listing_approved.greeting'))
            ->line(__('app.emails.listing_approved.intro', ['title' => $this->listingTitle]))
            ->line(__('app.emails.listing_approved.outro'));
    }
}
