<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConversationService
{
    /**
     * Return the buyer's existing conversation for the listing, or create one.
     *
     * The owner is denormalised from the listing so inbox queries never need to
     * join through listings. An optional opening message is only created for a
     * freshly-started conversation.
     */
    public function startOrGet(Listing $listing, User $buyer, ?string $message = null): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            ['listing_id' => $listing->id, 'buyer_id' => $buyer->id],
            ['owner_id' => $listing->user_id],
        );

        if ($conversation->wasRecentlyCreated && filled($message)) {
            $this->sendMessage($conversation, $buyer, $message);
        }

        return $conversation;
    }

    public function sendMessage(Conversation $conversation, User $sender, string $body): ChatMessage
    {
        $message = $conversation->messages()->create([
            'sender_id' => $sender->id,
            'body' => $body,
        ]);

        $conversation->touch();

        ChatMessageSent::dispatch($message);

        return $message;
    }

    /**
     * Return the user's conversations (as buyer or owner) as inbox payloads,
     * ordered by most-recent activity. Conversations whose other participant is
     * suspended are excluded, matching the view policy.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getInboxFor(User $user): Collection
    {
        return Conversation::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('buyer_id', $user->id)
                    ->orWhere('owner_id', $user->id);
            })
            ->with(['listing:id,title,slug', 'buyer:id,name,status', 'owner:id,name,status', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get()
            ->reject(fn (Conversation $conversation): bool => $conversation->otherParticipant($user)->isSuspended())
            ->map(fn (Conversation $conversation): array => $conversation->toInboxArray($user))
            ->values();
    }
}
