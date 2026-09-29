<?php

namespace App\Policies;

use App\Models\SavedSearch;
use App\Models\User;

class SavedSearchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ! $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isActive() && ! $user->isAdmin();
    }

    public function update(User $user, SavedSearch $savedSearch): bool
    {
        return $savedSearch->user_id === $user->id;
    }

    public function delete(User $user, SavedSearch $savedSearch): bool
    {
        return $savedSearch->user_id === $user->id;
    }
}
