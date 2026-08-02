<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportedChatMessage;
use App\Services\ReportedChatMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportedMessageModerationController extends Controller
{
    public function __construct(private ReportedChatMessageService $service) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', ReportedChatMessage::class);

        $reports = ReportedChatMessage::query()
            ->whereNull('resolved_at')
            ->with(['message.conversation.listing:id,title', 'reportedBy:id,name', 'offendingUser:id,name'])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ReportedChatMessage $report): array => $report->toModerationArray());

        return Inertia::render('Admin/ReportedMessages/Index', [
            'reports' => $reports,
            'translations' => __('app.admin.reported_messages'),
        ]);
    }

    public function resolve(ReportedChatMessage $report, Request $request): RedirectResponse
    {
        Gate::authorize('resolve', $report);

        $this->service->resolve($report, $request->user());

        return back()->with('success', __('app.admin.reported_messages.flash.resolved'));
    }

    public function suspend(ReportedChatMessage $report, Request $request): RedirectResponse
    {
        Gate::authorize('suspendOffendingUser', $report);

        $this->service->suspendOffendingUser($report, $request->user());

        return back()->with('success', __('app.admin.reported_messages.flash.suspended'));
    }
}
