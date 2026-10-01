<?php

namespace App\Http\Controllers;

use App\Services\ListingService;
use App\Services\Seo\PageSeo;
use App\Services\Seo\SeoHead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private ListingService $listingService,
    ) {}

    public function __invoke(Request $request, SeoHead $seoHead): Response
    {
        return Inertia::render('Home', [
            'landing' => __('app.landing'),
            'latestListings' => $this->listingService->getLatestListings(),
            'head' => $seoHead->render(new PageSeo(
                title: __('app.landing.title'),
                description: __('app.seo.pages.home'),
                preloadImageUrl: asset('images/hero-modern-house-white.webp'),
            ), $request),
        ]);
    }
}
