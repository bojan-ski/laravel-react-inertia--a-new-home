<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidAgencyStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectAgencyChangesRequest;
use App\Models\Agency;
use App\Services\AgencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AgencyChangeModerationController extends Controller
{
    public function __construct(private AgencyService $service) {}

    public function approve(Agency $agency): RedirectResponse
    {
        Gate::authorize('reviewChanges', $agency);

        try {
            $this->service->approveChanges($agency);
        } catch (InvalidAgencyStatusTransitionException) {
            return back()->with('error', __('app.admin.agencies.changes.flash.missing'));
        }

        return back()->with('success', __('app.admin.agencies.changes.flash.approved'));
    }

    public function reject(RejectAgencyChangesRequest $request, Agency $agency): RedirectResponse
    {
        try {
            $this->service->rejectChanges($agency, $request->validated('reason'));
        } catch (InvalidAgencyStatusTransitionException) {
            return back()->with('error', __('app.admin.agencies.changes.flash.missing'));
        }

        return back()->with('success', __('app.admin.agencies.changes.flash.rejected'));
    }
}
