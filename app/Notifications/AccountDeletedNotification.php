<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AccountDeletedNotification extends MailOnlyNotification
{
    public function __construct(public string $name) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.account_deleted.subject'))
            ->greeting(__('app.emails.account_deleted.greeting', ['name' => $this->name]))
            ->line(__('app.emails.account_deleted.intro'))
            ->line(__('app.emails.account_deleted.outro'));
    }
}
