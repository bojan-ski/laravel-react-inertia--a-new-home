<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyIndexRequest;
use App\Models\Agency;
use App\Services\AgencyService;
use App\Services\ListingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencyDirectoryController extends Controller
{
    public function __construct(
        private AgencyService $agencyService,
    ) {}

    public function index(AgencyIndexRequest $request): Response
    {
        $agencies = $this->agencyService->getPublicAgencies($request)
            ->through(fn (Agency $agency): array => $agency->toDirectoryArray());

        return Inertia::render('Agencies/Index', [
            'agencies' => $agencies,
            'filters' => $request->filters(),
            'translations' => __('app.agencies'),
        ]);
    }

    public function show(Request $request, Agency $agency, ListingService $listingService): Response
    {
        abort_unless($agency->isActuallyPubliclyVisible(), 404);

        return Inertia::render('Agencies/Show', [
            'agency' => $agency->toCardArray(),
            'listings' => $listingService->getPublicListingsForAgency($agency, $request->user()),
            'translations' => __('app.agencies'),
            'listingTranslations' => __('app.listings'),
        ]);
    }
}
