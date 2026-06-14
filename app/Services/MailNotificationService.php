<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccountDeletedNotification;
use App\Notifications\UserSuspendedNotification;
use Illuminate\Support\Facades\Notification;

class MailNotificationService
{
    public function sendUserSuspended(User $user): void
    {
        $user->notify(new UserSuspendedNotification);
    }

    public function sendAccountDeleted(string $name, string $email): void
    {
        Notification::route('mail', $email)->notify(new AccountDeletedNotification($name));
    }
}
