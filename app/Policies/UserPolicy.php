<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function suspend(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target) && $target->isActive();
    }

    public function reactivate(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target) && $target->isSuspended();
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    private function canManage(User $actor, User $target): bool
    {
        return $actor->isAdmin()
            && $actor->id !== $target->id
            && ! $target->isAdmin();
    }
}
