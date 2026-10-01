<?php

namespace App\Http\Controllers;

use App\Services\Seo\PageSeo;
use App\Services\Seo\SeoHead;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaticPageController extends Controller
{
    public function __construct(private SeoHead $seoHead) {}

    public function about(Request $request): Response
    {
        return $this->render($request, 'about');
    }

    public function privacy(Request $request): Response
    {
        return $this->render($request, 'privacy');
    }

    public function terms(Request $request): Response
    {
        return $this->render($request, 'terms');
    }

    public function cookies(Request $request): Response
    {
        return $this->render($request, 'cookies');
    }

    private function render(Request $request, string $page): Response
    {
        return Inertia::render('Static/Show', [
            'pageKey' => $page,
            'page' => __("pages.{$page}"),
            'translations' => __('app.static_page'),
            'head' => $this->seoHead->render(PageSeo::forPage(
                __("pages.{$page}.title"),
                __("pages.{$page}.lead"),
                $request->url(),
            ), $request),
        ]);
    }
}
