<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Services\Seo\CanonicalUrl;
use App\Services\Seo\SearchIndexing;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(SearchIndexing $indexing): Response
    {
        $lines = $indexing->isEnabled()
            ? ['User-agent: *', 'Disallow:', '', 'Sitemap: '.CanonicalUrl::route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $entries = [
            ...array_map(
                fn (string $route): array => ['loc' => CanonicalUrl::route($route), 'lastmod' => null],
                SearchIndexing::STATIC_ROUTES,
            ),
            ...Agency::query()->actuallyPubliclyVisible()->orderBy('id')->get()
                ->map(fn (Agency $agency): array => $agency->toSitemapArray()),
        ];

        return response()
            ->view('seo.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
