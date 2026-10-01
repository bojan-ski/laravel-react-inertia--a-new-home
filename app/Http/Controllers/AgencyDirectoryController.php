<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgencyIndexRequest;
use App\Models\Agency;
use App\Services\AgencyService;
use App\Services\ListingService;
use App\Services\Seo\PageSeo;
use App\Services\Seo\SeoHead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencyDirectoryController extends Controller
{
    public function __construct(
        private AgencyService $agencyService,
    ) {}

    public function index(AgencyIndexRequest $request, SeoHead $seoHead): Response
    {
        $agencies = $this->agencyService->getPublicAgencies($request)
            ->through(fn (Agency $agency): array => $agency->toDirectoryArray());

        return Inertia::render('Agencies/Index', [
            'agencies' => $agencies,
            'filters' => $request->filters(),
            'translations' => __('app.agencies'),
            'head' => $seoHead->render(PageSeo::forPage(
                __('app.agencies.title'),
                __('app.seo.pages.agencies'),
                route('agencies.index'),
            ), $request),
        ]);
    }

    public function show(Request $request, SeoHead $seoHead, Agency $agency, ListingService $listingService): Response
    {
        abort_unless($agency->isActuallyPubliclyVisible(), 404);

        return Inertia::render('Agencies/Show', [
            'agency' => $agency->toCardArray(),
            'listings' => $listingService->getPublicListingsForAgency($agency, $request->user()),
            'translations' => __('app.agencies'),
            'listingTranslations' => __('app.listings'),
            'head' => $seoHead->render(new PageSeo(
                title: $agency->name,
                description: $agency->metaDescription(),
                breadcrumbs: [
                    ['name' => __('app.seo.breadcrumb_home'), 'url' => route('home')],
                    ['name' => __('app.agencies.title'), 'url' => route('agencies.index')],
                    ['name' => $agency->name, 'url' => route('agencies.show', $agency->slug)],
                ],
                structuredData: [$agency->toStructuredDataArray()],
            ), $request),
        ]);
    }
}
