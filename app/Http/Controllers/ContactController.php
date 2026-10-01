<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Services\ContactMessageService;
use App\Services\Seo\PageSeo;
use App\Services\Seo\SeoHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private ContactMessageService $service) {}

    public function show(Request $request, SeoHead $seoHead): Response
    {
        return Inertia::render('Contact/Show', [
            'defaultEmail' => $request->user()?->email ?? '',
            'translations' => __('app.contact'),
            'head' => $seoHead->render(PageSeo::forPage(
                __('app.contact.title'),
                __('app.contact.lead'),
                route('contact.show'),
            ), $request),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $this->service->create($request->safe()->except('website'), $request->user());

        return redirect()->route('contact.show')->with('success', __('app.contact.flash'));
    }
}
