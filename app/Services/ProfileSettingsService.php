<?php

namespace App\Services;

use App\Models\User;

class ProfileSettingsService
{
    public function updatePassword(User $user, string $newPassword): User
    {
        $user->update([
            'password' => $newPassword,
        ]);

        return $user->fresh();
    }
}
