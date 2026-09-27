<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AgencyChangesApprovedNotification extends MailOnlyNotification
{
    public function __construct(public string $agencyName) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.agency_changes_approved.subject'))
            ->greeting(__('app.emails.agency_changes_approved.greeting'))
            ->line(__('app.emails.agency_changes_approved.intro', ['name' => $this->agencyName]))
            ->line(__('app.emails.agency_changes_approved.outro'));
    }
}
