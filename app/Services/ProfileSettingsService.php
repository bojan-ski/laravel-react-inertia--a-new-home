<?php

namespace App\Services;

use App\Models\User;

class ProfileSettingsService
{
    public function updateAccount(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user->fresh();
    }

    public function updatePassword(User $user, string $newPassword): User
    {
        $user->update([
            'password' => $newPassword,
        ]);

        return $user->fresh();
    }
}
