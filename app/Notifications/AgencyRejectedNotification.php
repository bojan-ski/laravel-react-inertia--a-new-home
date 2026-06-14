<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $agencyName, public string $reason) {}

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
            ->subject(__('app.emails.agency_rejected.subject'))
            ->greeting(__('app.emails.agency_rejected.greeting'))
            ->line(__('app.emails.agency_rejected.intro', ['name' => $this->agencyName]))
            ->line(__('app.emails.agency_rejected.reason', ['reason' => $this->reason]))
            ->line(__('app.emails.agency_rejected.outro'));
    }
}
