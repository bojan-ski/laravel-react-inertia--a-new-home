<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConversationPolicy
{
    /**
     * Non-participants are denied as not found (404) so a conversation's
     * existence never leaks; suspended participants are denied (403).
     */
    public function view(User $user, Conversation $conversation): Response
    {
        if (! $conversation->involvesUser($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->isSuspended()) {
            return Response::deny();
        }

        if ($this->otherParticipantSuspended($user, $conversation)) {
            return Response::deny();
        }

        return Response::allow();
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if (! $conversation->involvesUser($user)) {
            return false;
        }

        if ($conversation->buyer_id === null || $conversation->owner_id === null || $conversation->listing === null) {
            return false;
        }

        return ! $this->otherParticipantSuspended($user, $conversation);
    }

    public function start(User $user, Listing $listing): bool
    {
        if ($user->isSuspended()) {
            return false;
        }

        if (! $listing->isActuallyPubliclyVisible()) {
            return false;
        }

        $ownerAccount = $listing->ownerAccount();

        return $ownerAccount !== null && $ownerAccount->id !== $user->id;
    }

    private function otherParticipantSuspended(User $user, Conversation $conversation): bool
    {
        $conversation->loadMissing(['buyer', 'owner']);

        $other = $conversation->buyer_id === $user->id
            ? $conversation->owner
            : $conversation->buyer;

        return $other !== null && $other->isSuspended();
    }
}
