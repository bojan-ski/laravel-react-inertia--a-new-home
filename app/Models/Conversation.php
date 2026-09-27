<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['listing_id', 'listing_title', 'buyer_id', 'owner_id'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buyer_last_read_at' => 'datetime',
            'owner_last_read_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function involvesUser(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->owner_id === $user->id;
    }

    public function otherParticipant(User $actor): ?User
    {
        return $this->buyer_id === $actor->id ? $this->owner : $this->buyer;
    }

    public function otherParticipantId(User $actor): ?int
    {
        return $this->buyer_id === $actor->id ? $this->owner_id : $this->buyer_id;
    }

    public function lastReadColumnFor(User $user): string
    {
        return $this->buyer_id === $user->id ? 'buyer_last_read_at' : 'owner_last_read_at';
    }

    /**
     * @return array<string, mixed>
     */
    private function listingSummary(): array
    {
        return [
            'id' => $this->listing?->id,
            'title' => $this->listing?->title ?? $this->listing_title ?? __('app.conversations.listing_removed'),
            'slug' => $this->listing?->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function participantSummary(?User $other): array
    {
        return [
            'id' => $other?->id,
            'name' => $other?->name ?? __('app.conversations.deleted_user'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toInboxArray(User $actor): array
    {
        $other = $this->otherParticipant($actor);
        $lastMessage = $this->latestMessage;

        return [
            'id' => $this->id,
            'listing' => $this->listingSummary(),
            'otherParticipant' => $this->participantSummary($other),
            'lastMessage' => $lastMessage !== null ? [
                'body' => $lastMessage->body,
                'createdAt' => $lastMessage->created_at->toIso8601String(),
            ] : null,
            'unreadCount' => (int) ($this->unread_count ?? 0),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toShowArray(User $actor): array
    {
        $other = $this->otherParticipant($actor);

        return [
            'id' => $this->id,
            'listing' => $this->listingSummary(),
            'otherParticipant' => $this->participantSummary($other),
            'messages' => $this->messages->map(fn (ChatMessage $message): array => [
                'id' => $message->id,
                'senderId' => $message->sender_id,
                'senderName' => $message->sender?->name ?? __('app.conversations.deleted_user'),
                'body' => $message->body,
                'createdAt' => $message->created_at->toIso8601String(),
            ])->all(),
        ];
    }
}
