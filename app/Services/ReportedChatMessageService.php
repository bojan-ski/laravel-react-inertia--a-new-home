<?php

namespace App\Services;

use App\Exceptions\InvalidMessageReportException;
use App\Models\ChatMessage;
use App\Models\ReportedChatMessage;
use App\Models\User;

class ReportedChatMessageService
{
    public function __construct(private UserAccountService $userAccounts) {}

    public function report(ChatMessage $message, User $reporter, string $reason): ReportedChatMessage
    {
        if ($reporter->isSuspended()) {
            throw new InvalidMessageReportException(__('app.reports.errors.suspended'));
        }

        $message->loadMissing('conversation');

        if ($message->conversation === null || ! $message->conversation->involvesUser($reporter)) {
            throw new InvalidMessageReportException(__('app.reports.errors.not_participant'));
        }

        if ($message->sender_id === $reporter->id) {
            throw new InvalidMessageReportException(__('app.reports.errors.self_report'));
        }

        $alreadyReported = ReportedChatMessage::query()
            ->where('chat_message_id', $message->id)
            ->where('reported_by_user_id', $reporter->id)
            ->exists();

        if ($alreadyReported) {
            throw new InvalidMessageReportException(__('app.reports.errors.duplicate'));
        }

        return ReportedChatMessage::create([
            'chat_message_id' => $message->id,
            'reported_by_user_id' => $reporter->id,
            'offending_user_id' => $message->sender_id,
            'reason' => $reason,
        ]);
    }

    public function resolve(ReportedChatMessage $report, User $admin): ReportedChatMessage
    {
        $report->forceFill([
            'resolved_at' => now(),
            'resolved_by_user_id' => $admin->id,
        ])->save();

        return $report->fresh();
    }

    public function suspendOffendingUser(ReportedChatMessage $report, User $admin): ReportedChatMessage
    {
        $offender = $report->offendingUser;

        if ($offender !== null && $offender->isActive()) {
            $this->userAccounts->suspend($offender);
        }

        return $this->resolve($report, $admin);
    }
}
