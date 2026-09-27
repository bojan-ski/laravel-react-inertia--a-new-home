<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class ChatMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message,
        public int $recipientId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->recipientId);
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing(['sender', 'conversation']);

        return [
            'conversationId' => $this->message->conversation_id,
            'senderName' => $this->message->sender?->name ?? __('app.conversations.deleted_user'),
            'preview' => Str::limit($this->message->body, 80),
            'listingTitle' => $this->message->conversation?->listing_title,
        ];
    }
}
