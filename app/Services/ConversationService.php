<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Events\ChatMessageReceived;
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
     * Eager loads behind a conversation's listing summary, shared by the inbox
     * and the thread so both payloads read the same columns.
     */
    public const LISTING_SUMMARY_RELATIONS = [
        'listing:id,title,slug,status,listing_type,price,currency,city,approved_at,expires_at',
        'listing.images:id,listing_id,disk,small_path,sort_order',
    ];

    /**
     * Return the buyer's existing conversation for the listing, or create one.
     *
     * The owner is denormalised from the listing so inbox queries never need to
     * join through listings. An optional opening message is only created for a
     * freshly-started conversation.
     */
    public function startOrGet(Listing $listing, User $buyer, ?string $message = null): Conversation
    {
        $conversation = Conversation::query()
            ->where('listing_id', $listing->id)
            ->where('buyer_id', $buyer->id)
            ->first();

        if ($conversation === null) {
            $conversation = new Conversation(['listing_title' => $listing->title]);
            $conversation->listing_id = $listing->id;
            $conversation->buyer_id = $buyer->id;
            $conversation->owner_id = $listing->ownerAccount()?->id;
            $conversation->save();
        }

        if ($conversation->wasRecentlyCreated && filled($message)) {
            $this->sendMessage($conversation, $buyer, $message);
        }

        return $conversation;
    }

    public function sendMessage(Conversation $conversation, User $sender, string $body): ChatMessage
    {
        $message = new ChatMessage(['body' => $body]);
        $message->sender_id = $sender->id;
        $conversation->messages()->save($message);

        $conversation->touch();

        ChatMessageSent::dispatch($message);

        $recipientId = $conversation->otherParticipantId($sender);

        if ($recipientId !== null) {
            ChatMessageReceived::dispatch($message, $recipientId);
        }

        return $message;
    }

    public function markRead(Conversation $conversation, User $user): void
    {
        Conversation::withoutTimestamps(
            fn () => $conversation->forceFill([$conversation->lastReadColumnFor($user) => now()])->save()
        );
    }

    public function unreadCountFor(User $user): int
    {
        $query = ChatMessage::query()
            ->join('conversations', 'conversations.id', '=', 'chat_messages.conversation_id');

        return $this->whereUnreadFor($query, $user)->count();
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
            ->with([...self::LISTING_SUMMARY_RELATIONS, 'buyer:id,name,status', 'owner:id,name,status', 'owner.agency:id,user_id,name', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $this->whereUnreadFor($query, $user)])
            ->orderByDesc('updated_at')
            ->get()
            ->reject(fn (Conversation $conversation): bool => (bool) $conversation->otherParticipant($user)?->isSuspended())
            ->map(fn (Conversation $conversation): array => $conversation->toInboxArray($user))
            ->values();
    }

    private function whereUnreadFor(Builder $messages, User $user): Builder
    {
        return $messages
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('chat_messages.sender_id')
                    ->orWhere('chat_messages.sender_id', '!=', $user->id);
            })
            ->whereDoesntHave('sender', fn (Builder $query) => $query->where('status', UserStatus::Suspended->value))
            ->where(function (Builder $query) use ($user) {
                $query->where(function (Builder $query) use ($user) {
                    $query->where('conversations.buyer_id', $user->id)
                        ->where(function (Builder $query) {
                            $query->whereNull('conversations.buyer_last_read_at')
                                ->orWhereColumn('chat_messages.created_at', '>', 'conversations.buyer_last_read_at');
                        });
                })->orWhere(function (Builder $query) use ($user) {
                    $query->where('conversations.owner_id', $user->id)
                        ->where(function (Builder $query) {
                            $query->whereNull('conversations.owner_last_read_at')
                                ->orWhereColumn('chat_messages.created_at', '>', 'conversations.owner_last_read_at');
                        });
                });
            });
    }
}
