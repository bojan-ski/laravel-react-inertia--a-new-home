<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AgencyApprovedNotification extends MailOnlyNotification
{
    public function __construct(public string $agencyName) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.agency_approved.subject'))
            ->greeting(__('app.emails.agency_approved.greeting'))
            ->line(__('app.emails.agency_approved.intro', ['name' => $this->agencyName]))
            ->line(__('app.emails.agency_approved.outro'));
    }
}
