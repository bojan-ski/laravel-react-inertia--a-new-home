<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyProfileRequest;
use App\Services\AgencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencyProfileController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->agency !== null) {
            return redirect()->route('agency.show');
        }

        return Inertia::render('Agency/Setup', [
            'translations' => __('app.agency_setup'),
        ]);
    }

    public function store(AgencyProfileRequest $request, AgencyService $agencyService): RedirectResponse
    {
        if ($request->user()->agency !== null) {
            return redirect()->route('agency.show');
        }

        $agencyService->createForUser($request->user(), $request->validated());

        return redirect()->route('agency.show')->with('success', __('app.agency_setup.flash'));
    }

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        return Inertia::render('Agency/Dashboard', [
            'agency' => $request->user()->agency->toModerationArray(),
            'translations' => __('app.agency_dashboard'),
        ]);
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        if ($request->user()->agency === null) {
            return redirect()->route('agency.setup');
        }

        return Inertia::render('Agency/Dashboard', [
            'agency' => $request->user()->agency->toModerationArray(),
            'translations' => __('app.agency_dashboard'),
        ]);
    }
}
