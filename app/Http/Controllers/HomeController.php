<?php

namespace App\Http\Controllers;

use App\Services\ListingService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private ListingService $listingService,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'landing' => __('app.landing'),
            'latestListings' => $this->listingService->getLatestListings(),
        ]);
    }
}
