<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyProfileRequest;
use App\Models\Listing;
use App\Services\AgencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AgencyProfileController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->agency !== null) {
            return redirect()->route('agency.index');
        }

        return Inertia::render('Agency/Setup', [
            'translations' => __('app.agency_setup'),
        ]);
    }

    public function store(AgencyProfileRequest $request, AgencyService $agencyService): RedirectResponse
    {
        if ($request->user()->agency !== null) {
            return redirect()->route('agency.index');
        }

        $agencyService->createForUser($request->user(), $request->validated());

        return redirect()->route('agency.index')->with('success', __('app.agency_setup.flash'));
    }

    public function show(Request $request): Response
    {
        $agency = $request->user()->agency;
        Gate::authorize('view', $agency);

        $listings = $agency->listings()
            ->with('images')
            ->latest()
            ->get()
            ->map(fn (Listing $listing): array => $listing->toOwnerDashboardArray());

        return Inertia::render('Agency/Show', [
            'agency' => $agency->toShowArray(),
            'listings' => $listings,
            'translations' => __('app.agency_show'),
            'propertyTypeLabels' => __('app.listings.filters.property_types'),
        ]);
    }

    public function edit(Request $request): Response
    {
        $agency = $request->user()->agency;
        Gate::authorize('update', $agency);

        return Inertia::render('Agency/Edit', [
            'agency' => $agency->toFormArray(),
            'translations' => __('app.agency_edit'),
        ]);
    }

    public function update(AgencyProfileRequest $request, AgencyService $agencyService): RedirectResponse
    {
        $agency = $request->user()->agency;
        Gate::authorize('update', $agency);

        $agencyService->update($agency, $request->validated());

        return redirect()->route('agency.index')->with('success', __('app.agency_edit.flash'));
    }
}
