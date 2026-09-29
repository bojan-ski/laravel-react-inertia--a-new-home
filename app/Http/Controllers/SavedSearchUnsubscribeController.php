<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SavedSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SavedSearchUnsubscribeController extends Controller
{
    public function __construct(private SavedSearchService $service) {}

    public function show(User $user): Response
    {
        return Inertia::render('SavedSearches/Unsubscribe', [
            'confirmUrl' => URL::signedRoute('saved-searches.unsubscribe', ['user' => $user->id]),
            'translations' => __('app.saved_searches.unsubscribe'),
        ]);
    }

    public function store(User $user): RedirectResponse
    {
        $this->service->disableAllAlerts($user);

        return redirect()
            ->to(URL::signedRoute('saved-searches.unsubscribe.show', ['user' => $user->id]))
            ->with('success', __('app.saved_searches.unsubscribe.done'));
    }
}
