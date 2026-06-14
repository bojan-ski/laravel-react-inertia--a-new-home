<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSuspendedNotification extends Notification
{
    use Queueable;

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
            ->subject(__('app.emails.user_suspended.subject'))
            ->greeting(__('app.emails.user_suspended.greeting'))
            ->line(__('app.emails.user_suspended.intro'))
            ->line(__('app.emails.user_suspended.outro'));
    }
}
