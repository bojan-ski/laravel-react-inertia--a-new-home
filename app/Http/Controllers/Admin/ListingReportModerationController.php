<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidListingStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TakeDownListingRequest;
use App\Models\Listing;
use App\Models\ListingReport;
use App\Services\ListingReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ListingReportModerationController extends Controller
{
    public function __construct(private ListingReportService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ListingReport::class);

        $admin = $request->user();

        $listings = Listing::query()
            ->whereHas('openReports')
            ->with([
                'images',
                'openReports.reportedBy:id,name',
                'owner:id,name,email',
                'agency:id,name,email',
            ])
            ->withCount('openReports')
            ->withMax('openReports', 'created_at')
            ->orderByDesc('open_reports_max_created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Listing $listing): array => $listing->toReportModerationArray($admin));

        return Inertia::render('Admin/ListingReports/Index', [
            'listings' => $listings,
            'translations' => __('app.admin.listing_reports'),
            'reasonLabels' => __('app.listing_reports.reasons'),
        ]);
    }

    public function dismiss(Request $request, Listing $listing): RedirectResponse
    {
        Gate::authorize('dismiss', [ListingReport::class, $listing]);

        $this->service->dismiss($listing, $request->user());

        return back()->with('success', __('app.admin.listing_reports.flash.dismissed'));
    }

    public function takeDown(TakeDownListingRequest $request, Listing $listing): RedirectResponse
    {
        try {
            $this->service->takeDown($listing, $request->user(), $request->validated('reason'));
        } catch (InvalidListingStatusTransitionException) {
            return back()->with('error', __('app.admin.listing_reports.flash.take_down_failed'));
        }

        return back()->with('success', __('app.admin.listing_reports.flash.taken_down'));
    }
}
