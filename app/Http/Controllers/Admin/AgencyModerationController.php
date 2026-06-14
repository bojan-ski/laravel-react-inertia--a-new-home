<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Exceptions\InvalidAgencyStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectAgencyRequest;
use App\Models\Agency;
use App\Services\AgencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgencyModerationController extends Controller
{
    public function __construct(private AgencyService $service) {}

    public function index(): Response
    {
        $agencies = Agency::byStatus(AgencyStatus::PendingVerification)
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map->toModerationArray();

        return Inertia::render('Admin/Agencies/Index', [
            'agencies' => $agencies,
            'translations' => __('app.admin.agencies'),
        ]);
    }

    public function approve(Agency $agency): RedirectResponse
    {
        Gate::authorize('approve', $agency);

        try {
            $this->service->approve($agency);
        } catch (InvalidAgencyStatusTransitionException) {
            return back()->with('error', __('app.admin.agencies.flash.approve_failed'));
        }

        return back()->with('success', __('app.admin.agencies.flash.approved'));
    }

    public function reject(RejectAgencyRequest $request, Agency $agency): RedirectResponse
    {
        Gate::authorize('reject', $agency);

        try {
            $this->service->reject($agency, $request->validated('reason'));
        } catch (InvalidAgencyStatusTransitionException) {
            return back()->with('error', __('app.admin.agencies.flash.reject_failed'));
        }

        return back()->with('success', __('app.admin.agencies.flash.rejected'));
    }
}
