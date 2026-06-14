<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $agencyName) {}

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
            ->subject(__('app.emails.agency_approved.subject'))
            ->greeting(__('app.emails.agency_approved.greeting'))
            ->line(__('app.emails.agency_approved.intro', ['name' => $this->agencyName]))
            ->line(__('app.emails.agency_approved.outro'));
    }
}
