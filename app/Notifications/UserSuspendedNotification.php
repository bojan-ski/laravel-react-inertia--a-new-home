<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class UserSuspendedNotification extends MailOnlyNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('app.emails.user_suspended.subject'))
            ->greeting(__('app.emails.user_suspended.greeting'))
            ->line(__('app.emails.user_suspended.intro'))
            ->line(__('app.emails.user_suspended.outro'));
    }
}
