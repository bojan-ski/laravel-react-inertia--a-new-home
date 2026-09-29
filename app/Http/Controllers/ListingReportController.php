<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidListingReportException;
use App\Http\Requests\StoreListingReportRequest;
use App\Models\Listing;
use App\Services\ListingReportService;
use Illuminate\Http\RedirectResponse;

class ListingReportController extends Controller
{
    public function __construct(private ListingReportService $service) {}

    public function store(StoreListingReportRequest $request, Listing $listing): RedirectResponse
    {
        try {
            $this->service->report($listing, $request->user(), $request->validated());
        } catch (InvalidListingReportException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('app.listing_reports.flash.reported'));
    }
}
