<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidMessageReportException;
use App\Http\Requests\Conversation\StoreReportedChatMessageRequest;
use App\Models\ChatMessage;
use App\Services\ReportedChatMessageService;
use Illuminate\Http\RedirectResponse;

class MessageReportController extends Controller
{
    public function store(
        StoreReportedChatMessageRequest $request,
        ChatMessage $message,
        ReportedChatMessageService $service,
    ): RedirectResponse {
        try {
            $service->report($message, $request->user(), $request->validated('reason'));
        } catch (InvalidMessageReportException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('app.reports.flash.reported'));
    }
}
