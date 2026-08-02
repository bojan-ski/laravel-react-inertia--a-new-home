<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AgencyRejectedNotification extends MailOnlyNotification
{
    public function __construct(public string $agencyName, public string $reason) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.agency_rejected.subject'))
            ->greeting(__('app.emails.agency_rejected.greeting'))
            ->line(__('app.emails.agency_rejected.intro', ['name' => $this->agencyName]))
            ->line(__('app.emails.agency_rejected.reason', ['reason' => $this->reason]))
            ->line(__('app.emails.agency_rejected.outro'));
    }
}
