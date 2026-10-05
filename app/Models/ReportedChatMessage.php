<?php

namespace App\Models;

use Database\Factories\ReportedChatMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reason'])]
class ReportedChatMessage extends Model
{
    /** @use HasFactory<ReportedChatMessageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function offendingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'offending_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function canBeModerated(): bool
    {
        return ! $this->isResolved();
    }

    /**
     * @return array<string, mixed>
     */
    public function toModerationArray(): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'createdAt' => $this->created_at->toIso8601String(),
            'listingTitle' => $this->message?->conversation?->listing?->title,
            'conversationId' => $this->message?->conversation_id,
            'messageBody' => $this->message?->body,
            'messageSenderName' => $this->offendingUser?->name,
            'reportedByName' => $this->reportedBy?->name,
            'offendingUserName' => $this->offendingUser?->name,
        ];
    }
}
