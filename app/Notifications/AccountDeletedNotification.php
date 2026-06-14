<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $name) {}

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
            ->subject(__('app.emails.account_deleted.subject'))
            ->greeting(__('app.emails.account_deleted.greeting', ['name' => $this->name]))
            ->line(__('app.emails.account_deleted.intro'))
            ->line(__('app.emails.account_deleted.outro'));
    }
}
