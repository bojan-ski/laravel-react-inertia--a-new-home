<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyIndexRequest;
use App\Models\Agency;
use App\Services\AgencyService;
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
            ->through(fn (Agency $agency): array => $agency->toCardArray());

        return Inertia::render('Agencies/Index', [
            'agencies' => $agencies,
            'filters' => $request->filters(),
            'translations' => __('app.agencies'),
            'listingsNav' => __('app.listings.nav'),
        ]);
    }
}
